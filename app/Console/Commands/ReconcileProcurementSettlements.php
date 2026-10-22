<?php

namespace App\Console\Commands;

use App\Models\ProcurementOrder;
use App\Services\ProcurementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Find procurement orders that were delivered but never settled and reconcile
 * them.
 *
 * The procurement synchronisation and the supplier settlement are two separate
 * transactions. A process that dies between them leaves a procurement order
 * that is delivered while its settlement marker is still missing, and the
 * supplier was never credited. This command finds those records and, when it
 * is allowed to write, settles them through the same service path the
 * application uses.
 *
 * Exit codes:
 *   0  the run finished and nothing needs manual attention
 *   1  the run finished but some records are ambiguous or failed
 *   2  the command was called with an invalid combination of options
 */
class ReconcileProcurementSettlements extends Command
{
    protected $signature = 'money:reconcile-procurement-settlements
                            {--dry-run : Report the records only and change nothing (the default)}
                            {--write : Reconcile the records that pass the safety checks}
                            {--limit=500 : The maximum number of records to process in one run}
                            {--po= : Inspect only the procurement order with this id or po_number}';

    protected $description = 'Find procurement orders that were delivered but never settled and reconcile them';

    public function handle(ProcurementService $procurement): int
    {
        if ($this->option('dry-run') && $this->option('write')) {
            $this->error('Use either --dry-run or --write, not both.');

            return 2;
        }

        $write = (bool) $this->option('write');

        $totals = [
            'discovered'  => 0,
            'reconciled'  => 0,
            'repairable'  => 0,
            'skipped'     => 0,
            'ambiguous'   => 0,
            'failed'      => 0,
        ];

        $rows = [];

        // The records that need attention. They are reported with the warning
        // below, so the warning alone is enough to act on it.
        $actionable = [];

        foreach ($this->candidates() as $po) {
            $totals['discovered']++;

            $identifier = [(int) $po->id, (string) $po->po_number, $po->supplier_id];

            try {
                $state = $procurement->settlementState($po);
            } catch (\Throwable $e) {
                $totals['failed']++;

                $rows[] = [...$identifier, 'failed', 'The settlement state could not be read: '.$e->getMessage()];
                $actionable[] = ['procurement_order_id' => $identifier[0], 'po_number' => $identifier[1], 'supplier_id' => $identifier[2], 'state' => 'failed'];

                continue;
            }

            if ($state === ProcurementService::SETTLEMENT_AMBIGUOUS) {
                $totals['ambiguous']++;

                $rows[] = [...$identifier, 'ambiguous', 'The ledger already shows a supplier credit but the settlement marker is missing. Manual review required, the balance was not changed.'];
                $actionable[] = ['procurement_order_id' => $identifier[0], 'po_number' => $identifier[1], 'supplier_id' => $identifier[2], 'state' => 'ambiguous'];

                continue;
            }

            if ($state !== ProcurementService::SETTLEMENT_OUTSTANDING) {
                $totals['skipped']++;

                $rows[] = [...$identifier, $state, 'Nothing to settle.'];

                continue;
            }

            if (! $write) {
                $totals['repairable']++;

                $rows[] = [...$identifier, 'repairable', 'Delivered, not settled and no supplier credit in the ledger.'];
                $actionable[] = ['procurement_order_id' => $identifier[0], 'po_number' => $identifier[1], 'supplier_id' => $identifier[2], 'state' => 'repairable'];

                continue;
            }

            try {
                $result = $procurement->reconcileSettlement($po);
            } catch (\Throwable $e) {
                $totals['failed']++;

                $rows[] = [...$identifier, 'failed', 'The settlement failed: '.$e->getMessage()];
                $actionable[] = ['procurement_order_id' => $identifier[0], 'po_number' => $identifier[1], 'supplier_id' => $identifier[2], 'state' => 'failed'];

                continue;
            }

            if ($result === ProcurementService::SETTLEMENT_RECONCILED) {
                $totals['reconciled']++;

                $rows[] = [...$identifier, 'reconciled', 'The supplier was credited.'];

                continue;
            }

            if ($result === ProcurementService::SETTLEMENT_AMBIGUOUS) {
                $totals['ambiguous']++;

                $rows[] = [...$identifier, 'ambiguous', 'The ledger already shows a supplier credit but the settlement marker is missing. Manual review required, the balance was not changed.'];

                continue;
            }

            $totals['skipped']++;

            $rows[] = [...$identifier, $result, 'Nothing to settle.'];
        }

        $this->table(['PO', 'Number', 'Supplier', 'State', 'Detail'], $rows);

        // The totals are printed one per line, so an operator can grep for a
        // single counter and the scheduler log stays readable.
        $this->line('Discovered: '.$totals['discovered']);
        $this->line('Reconciled: '.$totals['reconciled']);
        $this->line('Repairable: '.$totals['repairable']);
        $this->line('Skipped: '.$totals['skipped']);
        $this->line('Ambiguous: '.$totals['ambiguous']);
        $this->line('Failed: '.$totals['failed']);
        $this->line('Mode: '.($write ? 'write' : 'dry run'));

        $context = $totals + ['mode' => $write ? 'write' : 'dry run'];

        // The records that need attention are part of the warning, so an
        // operator who only reads the application log knows which procurement
        // orders to look at. The list is bounded; the full list is always in
        // the command output and in the scheduler log.
        $records = array_slice($actionable, 0, 25);

        if (count($records) < count($actionable)) {
            $context['records_omitted'] = count($actionable) - count($records);
        }

        $context['records'] = $records;

        // The summary of every run is informational. The production log level
        // is "warning", so an informational record is filtered out there.
        Log::info('Procurement settlement reconciliation finished', $context);

        if ($totals['ambiguous'] > 0 || $totals['failed'] > 0) {
            // A non-zero outcome has to stay visible at the configured log
            // level, so the counts are repeated as a warning.
            Log::warning('Procurement settlement reconciliation needs a manual review', $context);

            return 1;
        }

        if ($totals['repairable'] > 0) {
            // An unsettled delivery is actionable, so the counts are repeated
            // as a warning with the command that repairs them. A clean run
            // keeps its informational record only, so it does not add noise.
            Log::warning('Procurement settlement reconciliation found unsettled deliveries', $context + [
                'command' => 'php artisan money:reconcile-procurement-settlements --write',
            ]);
        }

        return 0;
    }

    /**
     * The procurement orders that were delivered but never settled.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\ProcurementOrder>
     */
    private function candidates()
    {
        $query = ProcurementOrder::query()
            ->where('status', 'delivered')
            ->whereNull('settled_at')
            ->orderBy('id');

        $po = $this->option('po');

        if (is_string($po) && $po !== '') {
            $query->where(function ($q) use ($po): void {
                $q->where('id', is_numeric($po) ? (int) $po : 0)
                    ->orWhere('po_number', $po);
            });
        }

        return $query->limit(max(1, (int) $this->option('limit')))->get();
    }
}
