<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sitemap:generate')->dailyAt('02:00');

// Release InstaPay reservations whose transfer window has elapsed: cancel the
// order, restore its stock and note it on the timeline (T7.4).
Schedule::command('orders:release-reservations')->everyFifteenMinutes();

// Backups (PRD §13 / BK.2): prune stale archives, take the daily DB + media
// backup at 03:00, then verify the latest backup is healthy. Pinned to
// Africa/Cairo explicitly (app default is UTC) so the window never drifts.
Schedule::command('backup:clean')->dailyAt('02:30')->timezone('Africa/Cairo')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('03:00')->timezone('Africa/Cairo')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('03:30')->timezone('Africa/Cairo');
