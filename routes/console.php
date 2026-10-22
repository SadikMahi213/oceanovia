<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Detection of procurement orders that were delivered but never settled.
 *
 * The procurement synchronisation and the supplier settlement are two
 * separate transactions, so a process that dies between them leaves a
 * delivered procurement order whose supplier was never credited. The
 * scheduled run only reports those records, it never moves money: repairing
 * them is an explicit operation.
 *
 * Inspect the outstanding records:
 *
 *   php artisan money:reconcile-procurement-settlements --dry-run
 *
 * Repair them:
 *
 *   php artisan money:reconcile-procurement-settlements --write
 *
 * The run is protected against overlapping executions. withoutOverlapping()
 * uses the configured cache store, so on more than one application server it
 * only excludes an overlap inside that server. onOneServer() closes that gap,
 * but it needs a cache store that all servers share: the deployment writes
 * CACHE_STORE=redis (see deploy/deploy.sh), which is shared. If the cache
 * store is changed to a local one, remove onOneServer() again, because a
 * local lock cannot coordinate the servers.
 *
 * The time is the scheduler timezone of the application, which is UTC
 * (config/app.php and the deployment both set it to UTC), so the task runs at
 * 03:15 UTC every day.
 */
Schedule::command('money:reconcile-procurement-settlements --dry-run')
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Report procurement orders that were delivered but never settled');
