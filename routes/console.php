<?php

use App\Jobs\CleanupExpiredTrendsJob;
use App\Jobs\FetchAfricaTrendsJob;
use App\Jobs\ResearchContentIdeasJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduler:heartbeat')->everyMinute();

Schedule::job(new FetchAfricaTrendsJob)->dailyAt('07:00')->timezone('Africa/Lagos');
Schedule::job(new ResearchContentIdeasJob)->weeklyOn(1, '08:00')->timezone('Africa/Lagos');
Schedule::job(new CleanupExpiredTrendsJob)->dailyAt('00:00')->timezone('Africa/Lagos');

// GSC data lags ~2 days, so a daily run picks up freshly-available rows.
Schedule::command('gsc:sync')->dailyAt('06:00')->timezone('Africa/Lagos')->withoutOverlapping();
