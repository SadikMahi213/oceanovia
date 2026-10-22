<?php

namespace Tests\Feature;

use App\Models\ProcurementOrder;
use App\Models\SupplierBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * R3.6 - the operational readiness of the settlement reconciliation.
 *
 * These tests cover the parts that make the scheduled dry run usable in a
 * deployment: that the schedule holds exactly one reconciliation task, that
 * the task can never write, that the cron installation is duplicate safe, and
 * that an actionable finding is visible at the production log level.
 */
class ProcurementSettlementOperationsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{level: string, message: string, context: array}> */
    private array $logRecords = [];

    /** @var array<int, string> */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The environment logs at the "warning" level, which is the level the
        // production server uses. That level filters the informational record
        // of a clean run, so the test opens the channel for every level in
        // order to observe the level at which the command writes.
        config(['logging.channels.single.level' => 'debug']);

        $log = app('log');
        $log->forgetChannel('stack');
        $log->forgetChannel('single');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($directory);
        }

        $this->temporaryDirectories = [];

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | The schedule
    |--------------------------------------------------------------------------
    */

    public function test_the_reconciliation_is_registered_exactly_once_in_the_scheduler(): void
    {
        $events = $this->reconciliationEvents();

        $this->assertCount(1, $events, 'The reconciliation must be registered exactly once.');
    }

    public function test_the_scheduled_reconciliation_always_runs_in_dry_run_mode(): void
    {
        $command = (string) $this->reconciliationEvents()->first()->command;

        $this->assertStringContainsString('money:reconcile-procurement-settlements', $command);
        $this->assertStringContainsString('--dry-run', $command);
        $this->assertStringNotContainsString('--write', $command);
    }

    /*
    |--------------------------------------------------------------------------
    | The cron installation
    |--------------------------------------------------------------------------
    */

    public function test_the_cron_installation_is_duplicate_safe(): void
    {
        $bash = $this->bashBinary();

        if ($bash === null) {
            $this->markTestSkipped('bash is not available in this environment, so the installer cannot be exercised.');
        }

        $fake = $this->fakeCrontabPath();
        $crontab = $this->fakeCrontabFile($fake);

        // A crontab that belongs to somebody else and must survive.
        file_put_contents($crontab, "# an entry that is not managed\n*/5 * * * * /usr/local/bin/other-job\n");

        $first = $this->runInstaller($bash, $fake);
        $afterFirst = (string) file_get_contents($crontab);

        $second = $this->runInstaller($bash, $fake);
        $afterSecond = (string) file_get_contents($crontab);

        $this->assertSame(0, $first['exit'], 'The first installation failed: '.$first['output']);
        $this->assertSame(0, $second['exit'], 'The second installation failed: '.$second['output']);

        $this->assertSame(
            $afterFirst,
            $afterSecond,
            'A second installation must not change the crontab.'
        );

        $this->assertCount(
            1,
            $this->activeLines($afterSecond, 'schedule:run'),
            'The scheduler entry must exist exactly once.'
        );

        $this->assertCount(
            1,
            $this->activeLines($afterSecond, 'deploy/backup.sh'),
            'The weekly backup entry must be preserved exactly once.'
        );

        $this->assertCount(
            1,
            array_filter(explode("\n", $afterSecond), fn (string $line) => str_contains($line, 'BEGIN oceanovia managed entries')),
            'The managed block must exist exactly once.'
        );

        $this->assertStringContainsString(
            '*/5 * * * * /usr/local/bin/other-job',
            $afterSecond,
            'An entry that is not managed must be preserved.'
        );

        $this->assertStringNotContainsString(
            '--write',
            $this->activeLines($afterSecond, 'schedule:run')[0],
            'The scheduler must never run the reconciliation in write mode.'
        );
    }

    public function test_the_cron_installation_fails_when_the_crontab_cannot_be_written(): void
    {
        $bash = $this->bashBinary();

        if ($bash === null) {
            $this->markTestSkipped('bash is not available in this environment, so the installer cannot be exercised.');
        }

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'oceanovia-cron-'.bin2hex(random_bytes(6));

        mkdir($directory, 0777, true);

        try {
            $result = $this->runInstaller($bash, $directory.DIRECTORY_SEPARATOR.'missing-crontab.sh');

            $this->assertSame(1, $result['exit'], 'The installer must fail when the crontab cannot be written.');
            $this->assertStringContainsString('could not be written', $result['output']);
        } finally {
            @rmdir($directory);
        }
    }

    public function test_the_cron_installation_fails_when_the_application_directory_does_not_exist(): void
    {
        $bash = $this->bashBinary();

        if ($bash === null) {
            $this->markTestSkipped('bash is not available in this environment, so the installer cannot be exercised.');
        }

        $result = $this->runInstaller($bash, $this->fakeCrontabPath(), [
            'APP_DIR' => $this->posixPath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'oceanovia-missing-'.bin2hex(random_bytes(6))),
        ]);

        $this->assertSame(1, $result['exit'], 'The installer must fail when the application directory is missing.');
        $this->assertStringContainsString('does not exist', $result['output']);
    }

    public function test_the_cron_installation_fails_when_the_application_directory_has_no_artisan_file(): void
    {
        $bash = $this->bashBinary();

        if ($bash === null) {
            $this->markTestSkipped('bash is not available in this environment, so the installer cannot be exercised.');
        }

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'oceanovia-cron-'.bin2hex(random_bytes(6));

        mkdir($directory, 0777, true);

        try {
            $result = $this->runInstaller($bash, $this->fakeCrontabPath(), [
                'APP_DIR' => $this->posixPath($directory),
            ]);

            $this->assertSame(1, $result['exit'], 'The installer must fail when the application directory has no artisan file.');
            $this->assertStringContainsString('artisan', $result['output']);
        } finally {
            @rmdir($directory);
        }
    }

    public function test_the_cron_installation_fails_when_the_php_binary_does_not_exist(): void
    {
        $bash = $this->bashBinary();

        if ($bash === null) {
            $this->markTestSkipped('bash is not available in this environment, so the installer cannot be exercised.');
        }

        $result = $this->runInstaller($bash, $this->fakeCrontabPath(), [
            'PHP_BIN' => $this->posixPath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'oceanovia-missing-php-'.bin2hex(random_bytes(6))),
        ]);

        $this->assertSame(1, $result['exit'], 'The installer must fail when the PHP binary is missing.');
        $this->assertStringContainsString('PHP executable', $result['output']);
    }

    public function test_the_installed_cron_entry_uses_the_configured_paths(): void
    {
        $bash = $this->bashBinary();

        if ($bash === null) {
            $this->markTestSkipped('bash is not available in this environment, so the installer cannot be exercised.');
        }

        $fake = $this->fakeCrontabPath();
        $crontab = $this->fakeCrontabFile($fake);

        $appDir = $this->posixPath(base_path());
        $phpBin = $this->posixPath(PHP_BINARY);

        $result = $this->runInstaller($bash, $fake, [
            'APP_DIR' => $appDir,
            'PHP_BIN' => $phpBin,
        ]);

        $this->assertSame(0, $result['exit'], 'The installation failed: '.$result['output']);

        $installed = (string) file_get_contents($crontab);
        $entry = $this->activeLines($installed, 'schedule:run')[0] ?? '';

        $this->assertStringContainsString($appDir, $entry, 'The scheduler entry must use the configured application directory.');
        $this->assertStringContainsString($phpBin, $entry, 'The scheduler entry must use the configured PHP binary.');
        $this->assertStringContainsString('cd "', $entry, 'The paths of the scheduler entry must be quoted.');
    }

    /*
    |--------------------------------------------------------------------------
    | The visibility of the findings
    |--------------------------------------------------------------------------
    */

    public function test_a_clean_run_logs_the_summary_as_information_only(): void
    {
        $this->captureLogRecords();

        $this->artisan('money:reconcile-procurement-settlements --dry-run')->assertExitCode(0);

        $this->assertTrue(
            $this->hasRecord($this->logRecords, 'info', 'Procurement settlement reconciliation finished'),
            'The summary of a clean run must be logged as information.'
        );

        $this->assertFalse(
            $this->hasRecord($this->logRecords, 'warning', 'Procurement settlement reconciliation'),
            'A clean run must not produce a warning.'
        );
    }

    public function test_an_unsettled_delivery_is_logged_as_a_warning_with_the_counts(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $this->deliveredButUnsettled($supplier, $seller);

        $this->captureLogRecords();

        $this->artisan('money:reconcile-procurement-settlements --dry-run')->assertExitCode(0);

        $this->assertTrue(
            $this->hasRecord($this->logRecords, 'warning', 'Procurement settlement reconciliation found unsettled deliveries'),
            'An unsettled delivery must be logged as a warning, because the production log level is "warning".'
        );
    }

    public function test_the_warning_names_the_records_that_need_attention(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->captureLogRecords();

        $this->artisan('money:reconcile-procurement-settlements --dry-run')->assertExitCode(0);

        $warning = null;

        foreach ($this->logRecords as $record) {
            if ($record['level'] === 'warning'
                && str_contains($record['message'], 'Procurement settlement reconciliation found unsettled deliveries')) {
                $warning = $record;
            }
        }

        $this->assertNotNull($warning, 'The unsettled delivery must be logged as a warning.');
        $this->assertSame(1, $warning['context']['repairable']);
        $this->assertSame($po->po_number, $warning['context']['records'][0]['po_number']);
        $this->assertSame((int) $po->id, $warning['context']['records'][0]['procurement_order_id']);
        $this->assertSame('repairable', $warning['context']['records'][0]['state']);
    }

    public function test_an_ambiguous_record_is_logged_as_a_warning_and_the_run_fails(): void    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        Transaction::create([
            'accountable_type' => SupplierBalance::class,
            'accountable_id'   => SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->id,
            'reference_type'   => ProcurementOrder::class,
            'reference_id'     => $po->id,
            'type'             => 'credit',
            'amount'           => 120.00,
            'description'      => 'Procurement settlement: '.$po->po_number,
            'status'           => 'completed',
            'method'           => 'procurement',
        ]);

        $this->captureLogRecords();

        $this->artisan('money:reconcile-procurement-settlements --dry-run')->assertExitCode(1);

        $this->assertTrue(
            $this->hasRecord($this->logRecords, 'warning', 'Procurement settlement reconciliation needs a manual review'),
            'A non-zero outcome must be logged as a warning.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Record every log message that is written from now on.
     *
     * @return array<int, array{level: string, message: string, context: array}>
     */
    private function captureLogRecords(): void
    {
        $this->logRecords = [];

        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->logRecords[] = [
                'level'   => $event->level,
                'message' => (string) $event->message,
                'context' => $event->context,
            ];
        });

    }

    /**
     * @param  array<int, array{level: string, message: string, context: array}>  $records
     */
    private function hasRecord(array $records, string $level, string $needle): bool
    {
        foreach ($records as $record) {
            if ($record['level'] === $level && str_contains($record['message'], $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The scheduled events that run the reconciliation.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Console\Scheduling\Event>
     */
    private function reconciliationEvents()
    {
        // The console routes register the schedule when the console boots.
        $this->artisan('schedule:list');

        return collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'money:reconcile-procurement-settlements'))
            ->values();
    }

    /**
     * The lines of the crontab that are active entries, not comments.
     *
     * @return array<int, string>
     */
    private function activeLines(string $crontab, string $needle): array
    {
        return array_values(array_filter(
            explode("\n", $crontab),
            fn (string $line) => str_contains($line, $needle) && ! str_starts_with(trim($line), '#')
        ));
    }

    /**
     * A crontab stand-in that keeps the crontab in a file.
     */
    private function fakeCrontabScript(string $path): string
    {
        return "#!/bin/sh\n"
            ."case \"\$1\" in\n"
            ."  -l) [ -f \"$path\" ] && cat \"$path\" || true ;;\n"
            ."  -)  cat > \"$path\" ;;\n"
            ."  *)  exit 1 ;;\n"
            ."esac\n";
    }

    /**
     * Run the cron installer against the given crontab stand-in.
     *
     * @param  array<string, string>  $environment
     * @return array{exit: int, output: string}
     */
    private function runInstaller(string $bash, string $fakeCrontab, array $environment = []): array
    {
        $script = $this->posixPath(base_path('deploy/install-cron.sh'));
        $fake = $this->posixPath($fakeCrontab);

        $variables = array_merge([
            'CRONTAB_CMD' => 'sh '.$fake,
            'APP_DIR'     => $this->posixPath(base_path()),
            'PHP_BIN'     => $this->posixPath(PHP_BINARY),
        ], $environment);

        $result = Process::env(array_merge(getenv(), $variables))
            ->path(base_path())
            ->run([$bash, $script]);

        return [
            'exit'   => $result->exitCode(),
            'output' => $result->output().$result->errorOutput(),
        ];
    }

    /**
     * A crontab stand-in in a temporary directory.
     */
    private function fakeCrontabPath(): string
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'oceanovia-cron-'.bin2hex(random_bytes(6));

        mkdir($directory, 0777, true);

        $this->temporaryDirectories[] = $directory;

        $crontab = $directory.DIRECTORY_SEPARATOR.'crontab';
        $fake = $directory.DIRECTORY_SEPARATOR.'crontab.sh';

        file_put_contents($crontab, '');
        file_put_contents($fake, $this->fakeCrontabScript($crontab));

        return $fake;
    }

    /**
     * The crontab file behind a crontab stand-in.
     */
    private function fakeCrontabFile(string $fakeCrontab): string
    {
        return dirname($fakeCrontab).DIRECTORY_SEPARATOR.'crontab';
    }

    /**
     * A path that the shell of this environment understands.
     */
    private function posixPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * The bash interpreter of this environment, if there is one.
     */
    private function bashBinary(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['C:\\Program Files\\Git\\bin\\bash.exe', 'C:\\Program Files\\Git\\usr\\bin\\bash.exe'] as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            }

            return null;
        }

        foreach (['/usr/bin/bash', '/bin/bash'] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function seller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        \App\Models\SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

        return $seller;
    }

    private function supplier(float $balance = 0.00): User
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);

        SupplierBalance::create([
            'supplier_id'     => $supplier->id,
            'balance'         => $balance,
            'pending_balance' => 0,
            'total_earned'    => $balance,
            'total_withdrawn' => 0,
            'platform_fees'   => 0,
        ]);

        return $supplier;
    }

    private function deliveredButUnsettled(User $supplier, User $seller, float $subtotal = 120.00): ProcurementOrder
    {
        $product = \App\Models\Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $subtotal,
        ]);

        $order = \App\Models\Order::create([
            'user_id'  => $seller->id,
            'subtotal' => $subtotal,
            'total'    => $subtotal,
            'status'   => 'confirmed',
        ]);

        $item = \App\Models\OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => $subtotal,
            'subtotal'     => $subtotal,
            'status'       => 'delivered',
        ]);

        $po = ProcurementOrder::create([
            'order_id'    => $order->id,
            'supplier_id' => $supplier->id,
            'seller_id'   => $seller->id,
            'status'      => 'delivered',
            'subtotal'    => $subtotal,
        ]);

        \App\Models\ProcurementOrderItem::create([
            'procurement_order_id' => $po->id,
            'order_item_id'        => $item->id,
            'product_id'           => $product->id,
            'product_name'         => $product->name,
            'quantity'             => 1,
            'unit_cost'            => 10.00,
            'subtotal'             => $subtotal,
            'status'               => 'delivered',
        ]);

        return $po;
    }
}
