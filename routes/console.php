<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every 4 hours: 00:00, 04:00, 08:00, 12:00, 16:00, 20:00.
// news:fetch also runs news:cleanup at the end (deletes articles older than 10 days).
Schedule::command('news:fetch')->cron('0 */4 * * *');

// The "Fetch now" button in the panel just sets this cache flag. Since the
// scheduler already runs every minute (via `schedule:run` in cron), this
// picks it up within ~1 minute without needing a separate background worker.
Schedule::command('news:fetch')
    ->everyMinute()
    ->when(fn () => Cache::pull('news_fetch_requested', false));
