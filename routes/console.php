<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The quiz clock and inactivity clock both need to tick even when nobody is
// browsing the site, so they run on the scheduler rather than only on request.
Schedule::command('app:auto-submit-expired-quizzes')->everyMinute();
Schedule::command('app:check-inactive-members')->daily();
