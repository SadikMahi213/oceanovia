<?php

/**
 * Worker process for the real-engine money path concurrency tests.
 *
 * It runs in its own PHP process, so it owns an independent database
 * connection and genuinely overlaps with the other worker. It drives the
 * service methods that the money paths use, and the checkout endpoints for the
 * checkout races.
 *
 * Usage: php tests/Support/MoneyPathConcurrencyWorker.php <payload-file> <barrier-dir>
 */

$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';

/**
 * A connection that records the database errors the application runs into.
 *
 * The application retries a transaction that failed with a concurrency error,
 * so a real InnoDB deadlock never reaches the response: the request succeeds
 * with the retried result. This subclass only watches the failures, it does
 * not change how a statement is run or how an error is handled, so the retry
 * behaviour under test stays the behaviour of the application.
 */
final class RecordingMySqlConnection extends Illuminate\Database\MySqlConnection
{
    /** @var array<int, array{sqlstate: string|null, code: int|null, sql: string}> */
    public static array $failures = [];

    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        try {
            return parent::runQueryCallback($query, $bindings, $callback);
        } catch (Illuminate\Database\QueryException $e) {
            static::$failures[] = [
                'sqlstate' => isset($e->errorInfo[0]) ? (string) $e->errorInfo[0] : null,
                'code'     => isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : null,
                'sql'      => substr((string) $e->getSql(), 0, 200),
            ];

            throw $e;
        }
    }
}

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// The recording connection replaces the plain MySQL connection of the
// application, so a failure that the framework retries is still visible.
Illuminate\Support\Facades\DB::extend('mysql', function (array $config) {
    $connector = new Illuminate\Database\Connectors\MySqlConnector();

    return new RecordingMySqlConnection(
        $connector->connect($config),
        (string) ($config['database'] ?? ''),
        (string) ($config['prefix'] ?? ''),
        $config
    );
});

Illuminate\Support\Facades\DB::purge('mysql');

[, $payloadFile, $barrier] = $argv;

$payload = json_decode((string) file_get_contents($payloadFile), true);
$workerId = (string) $payload['worker'];

file_put_contents($barrier.'/ready.'.$workerId, '1');

$deadline = microtime(true) + 30;
while (! file_exists($barrier.'/go')) {
    usleep(2000);
    if (microtime(true) > $deadline) {
        break;
    }
}

$result = ['worker' => $workerId, 'ok' => false];
/**
 * The messages the response carried.
 *
 * The web checkout reports an empty cart and a failed validation through the
 * session. Where exactly the framework keeps them depends on the point in the
 * request lifecycle: while the request runs they are a ViewErrorBag, and
 * before the session is written the error bag is serialised into an array.
 * All the shapes are read, so the capture does not depend on that detail.
 *
 * Only business messages are collected; no session identifier, cookie or token
 * is ever written out.
 *
 * @return array<int, string>
 */
$workerSessionMessages = function (): array {
    $messages = [];

    $collectBag = function ($bag) use (&$messages): void {
        if ($bag instanceof Illuminate\Support\ViewErrorBag) {
            $bag = $bag->getBag('default');
        }

        if ($bag instanceof Illuminate\Support\MessageBag) {
            $bag = $bag->toArray();
        }

        if (! is_array($bag)) {
            return;
        }

        // A serialised error bag keeps the name of the bag as its first level
        // ("default"), and a serialised message bag keeps its messages under
        // "messages". The two levels are descended in that order.
        foreach (['default', 'messages'] as $level) {
            if (isset($bag[$level]) && is_array($bag[$level])) {
                $bag = $bag[$level];
            }
        }

        foreach ($bag as $field => $list) {
            foreach ((array) $list as $message) {
                $messages[] = $field.': '.$message;
            }
        }
    };

    try {
        $old = (array) session()->get('_flash.old', []);
        $new = (array) session()->get('_flash.new', []);

        $collectBag(session()->get('errors'));
        $collectBag($old['errors'] ?? null);
        $collectBag($new['errors'] ?? null);

        foreach ([session()->get('error'), $old['error'] ?? null, $new['error'] ?? null] as $flash) {
            if (is_string($flash) && $flash !== '') {
                $messages[] = 'error: '.$flash;
            }
        }
    } catch (Throwable $e) {
        // A request without a session simply has no messages.
    }

    return array_values(array_unique($messages));
};

try {
    if ($payload['operation'] === 'settle') {
        $procurementOrder = App\Models\ProcurementOrder::findOrFail($payload['procurement_order_id']);

        app(App\Services\ProcurementService::class)->syncProcurement($procurementOrder);
    } elseif ($payload['operation'] === 'reconcile') {
        $procurementOrder = App\Models\ProcurementOrder::findOrFail($payload['procurement_order_id']);

        $result['state'] = app(App\Services\ProcurementService::class)->reconcileSettlement($procurementOrder);
    } elseif ($payload['operation'] === 'cod_collection') {
        $collection = App\Models\CodCollection::findOrFail($payload['collection_id']);

        app(App\Services\CodCollectionService::class)->recordCollection(
            $collection,
            (float) $payload['amount'],
            (int) $payload['collected_by']
        );
    } elseif ($payload['operation'] === 'deadlock_hold') {
        // Holds one row and then waits for another one, so the other side can
        // close the cycle and InnoDB has to resolve a real deadlock. The
        // tables are restricted to the two the deadlock test needs.
        $allowed = ['inventory', 'carts'];

        if (! in_array($payload['lock_table'], $allowed, true) || ! in_array($payload['wait_table'], $allowed, true)) {
            throw new RuntimeException('Unsupported table for the deadlock operation.');
        }

        $connection = Illuminate\Support\Facades\DB::connection();

        $connection->statement('START TRANSACTION');

        Illuminate\Support\Facades\DB::select(
            'SELECT id FROM `'.$payload['lock_table'].'` WHERE id = '.(int) $payload['lock_id'].' FOR UPDATE'
        );

        // The other side may now create the cycle.
        file_put_contents($barrier.'/holding.'.$workerId, '1');

        // This blocks until InnoDB detects the cycle and rolls one side back.
        Illuminate\Support\Facades\DB::select(
            'SELECT id FROM `'.$payload['wait_table'].'` WHERE id = '.(int) $payload['wait_id'].' FOR UPDATE'
        );

        $result['reached'] = true;

        $connection->statement('ROLLBACK');
    } elseif ($payload['operation'] === 'checkout_web' || $payload['operation'] === 'checkout_api') {
        $user = App\Models\User::findOrFail($payload['user_id']);
        $http = $app->make(Illuminate\Contracts\Http\Kernel::class);

        $input = (array) ($payload['input'] ?? []);

        if ($payload['operation'] === 'checkout_api') {
            Laravel\Sanctum\Sanctum::actingAs($user);

            // The API checkout reads the JSON body, so the payload is sent as
            // the request content.
            $request = Illuminate\Http\Request::create(
                $payload['uri'],
                'POST',
                [],
                [],
                [],
                [
                    'HTTP_ACCEPT'   => 'application/json',
                    'REMOTE_ADDR'  => '127.0.0.1',
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($input)
            );
        } else {
            Illuminate\Support\Facades\Auth::login($user);

            // The web middleware group verifies the CSRF token against the
            // session, so the request carries the session cookie and a token.
            $input['_token'] = csrf_token();
            $cookies = [session()->getName() => session()->getId()];

            $request = Illuminate\Http\Request::create(
                $payload['uri'],
                'POST',
                $input,
                [],
                [],
                [
                    'HTTP_ACCEPT'  => 'application/json',
                    'REMOTE_ADDR' => '127.0.0.1',
                    'HTTP_COOKIE' => http_build_query($cookies),
                ]
            );
        }

        $response = $http->handle($request);

        $result['status'] = $response->getStatusCode();
        $result['location'] = (string) $response->headers->get('Location');
        $result['body'] = substr((string) $response->getContent(), 0, 500);
        $result['messages'] = $workerSessionMessages();

        // The names of the session attributes and of the flash bags, never
        // their values, so a failing run can be diagnosed.
        try {
            $errors = session()->get('errors');
            $flashed = session()->get('_flash.old');

            $result['session_keys'] = array_keys(session()->all());
            $result['errors_type'] = is_object($errors) ? get_class($errors) : gettype($errors);
            $result['flash_old_type'] = is_object($flashed) ? get_class($flashed) : gettype($flashed);
            $result['flash_old_keys'] = is_array($flashed) ? array_keys($flashed) : [];
            $result['errors_keys'] = is_array($errors) ? array_keys($errors) : [];
            $result['errors_sub_keys'] = is_array($errors) ? array_map(fn ($v) => is_array($v) ? array_keys($v) : gettype($v), $errors) : [];
            $result['flash_new_keys'] = array_keys((array) session()->get('_flash.new', []));
        } catch (Throwable $e) {
            $result['session_keys'] = [];
            $result['errors_type'] = 'unavailable';
            $result['flash_old_type'] = 'unavailable';
            $result['flash_old_keys'] = [];
            $result['flash_new_keys'] = [];
        }

        // The order this very request created is identified from its own
        // response: the web checkout redirects to the order page and the API
        // checkout answers with the order URL. The orders of the user are not
        // queried, because a query after the request cannot tell which of the
        // requests created the order.
        $target = $result['location'] !== '' ? $result['location'] : $result['body'];

        if (preg_match('#/orders/(\d+)#', $target, $matches) === 1) {
            $result['created_order_id'] = (int) $matches[1];
        } else {
            $result['created_order_id'] = null;
        }
    } elseif ($payload['operation'] === 'create_order') {
        // Creates an order through the model, the same path a checkout uses,
        // and reports the number the order received.
        $user = App\Models\User::findOrFail($payload['user_id']);

        $order = App\Models\Order::create([
            'user_id'        => $user->id,
            'subtotal'       => 25.00,
            'shipping_cost'  => 0,
            'tax'            => 0,
            'discount'       => 0,
            'total'          => 25.00,
            'status'         => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $result['order_id'] = (int) $order->id;
        $result['order_number'] = (string) $order->order_number;

        // The number of a fixed instant as well: two processes that ask for
        // the number of the same instant must still get different numbers.
        if (! empty($payload['instant'])) {
            $result['instant_number'] = App\Models\Order::generateOrderNumber(
                new DateTimeImmutable((string) $payload['instant'])
            );
        }
    } elseif ($payload['operation'] === 'create_duplicate_order') {
        // Creates an order with an order number that is already taken, so the
        // unique index rejects it. The worker has to report the error the
        // server returned, and a duplicate key is not a deadlock.
        $user = App\Models\User::findOrFail($payload['user_id']);

        App\Models\Order::create([
            'user_id'        => $user->id,
            'order_number'   => (string) $payload['order_number'],
            'subtotal'       => 25.00,
            'shipping_cost'  => 0,
            'tax'            => 0,
            'discount'       => 0,
            'total'          => 25.00,
            'status'         => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);
    } else {
        throw new RuntimeException('Unknown operation: '.$payload['operation']);
    }

    $result['ok'] = true;
} catch (Throwable $e) {
    $result['error'] = get_class($e).': '.substr($e->getMessage(), 0, 200);

    if ($e instanceof Illuminate\Database\QueryException) {
        // The driver error code and the SQLSTATE tell a deadlock (1213 /
        // 40001) apart from a lock wait timeout (1205 / HY000).
        $result['sqlstate'] = $e->errorInfo[0] ?? null;
        $result['driver_code'] = $e->errorInfo[1] ?? null;
    }

    if ($e instanceof Illuminate\Database\DeadlockException) {
        $result['deadlock_exception'] = true;
    }
}

// The database errors the application ran into, including the ones its own
// retry recovered from.
$result['db_failures'] = RecordingMySqlConnection::$failures;

file_put_contents($barrier.'/result.'.$workerId, json_encode($result));
