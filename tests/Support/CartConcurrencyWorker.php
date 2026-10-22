<?php

/**
 * Worker process for the real-engine cart concurrency tests.
 *
 * It runs in its own PHP process, so it owns an independent database
 * connection and its own session, and it drives the real HTTP endpoints.
 *
 * Usage: php tests/Support/CartConcurrencyWorker.php <payload-file> <barrier-dir>
 */

$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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

try {
    $user = App\Models\User::findOrFail($payload['user_id']);
    $http = $app->make(Illuminate\Contracts\Http\Kernel::class);

    if ($payload['channel'] === 'api') {
        Laravel\Sanctum\Sanctum::actingAs($user);
    } else {
        Illuminate\Support\Facades\Auth::login($user);
    }

    $input = $payload['input'];
    $cookies = [];

    if ($payload['channel'] === 'web') {
        // The web middleware group verifies the CSRF token against the
        // session, so the request carries the session cookie and a token.
        $input['_token'] = csrf_token();
        $cookies[session()->getName()] = session()->getId();
    }

    $request = Illuminate\Http\Request::create(
        $payload['uri'],
        'POST',
        $input,
        [],
        [],
        [
            'HTTP_ACCEPT'   => 'application/json',
            'REMOTE_ADDR'  => '127.0.0.1',
            'HTTP_COOKIE'  => http_build_query($cookies),
        ]
    );

    $response = $http->handle($request);

    $result['status'] = $response->getStatusCode();
    $result['body'] = substr((string) $response->getContent(), 0, 500);
    $result['ok'] = true;
} catch (Throwable $e) {
    $result['error'] = get_class($e).': '.$e->getMessage();
}

file_put_contents($barrier.'/result.'.$workerId, json_encode($result));
