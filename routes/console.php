<?php

use App\Jobs\RH\NotifyExpiringContractsJob;
use App\Jobs\RH\TerminateExpiredContractsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduler ────────────────────────────────────────────────────────────────

Schedule::job(TerminateExpiredContractsJob::class)
    ->daily()
    ->name('terminate-expired-contracts')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(NotifyExpiringContractsJob::class)
    ->dailyAt('07:00')
    ->name('notify-expiring-contracts')
    ->withoutOverlapping()
    ->onOneServer();
