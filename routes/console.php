<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sitemap:generate')->weekly();

// Release InstaPay reservations whose transfer window has elapsed: cancel the
// order, restore its stock and note it on the timeline (T7.4).
Schedule::command('orders:release-reservations')->everyFifteenMinutes();
