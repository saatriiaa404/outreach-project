<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily cleanup of expired in-app notifications (Workflow v2.1 30.4, D-063).
// Runs with `php artisan schedule:work` or a Windows Task Scheduler entry for
// `php artisan schedule:run` (docs/engineering/setup.md).
Schedule::command('notifications:prune')->daily();
