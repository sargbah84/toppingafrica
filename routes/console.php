<?php

use App\Jobs\CleanupExpiredTrendsJob;
use App\Jobs\DailyContentAgentJob;
use App\Jobs\FetchAfricaTrendsJob;
use App\Jobs\PublishScheduledPostsJob;
use App\Jobs\ResearchContentIdeasJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduler:heartbeat')->everyMinute();

Schedule::job(new FetchAfricaTrendsJob)->dailyAt('07:00')->timezone('Africa/Lagos');
Schedule::job(new ResearchContentIdeasJob)->weeklyOn(1, '08:00')->timezone('Africa/Lagos');
Schedule::job(new CleanupExpiredTrendsJob)->dailyAt('00:00')->timezone('Africa/Lagos');

Schedule::job(new PublishScheduledPostsJob)->everyFiveMinutes()->withoutOverlapping();

// Fires every 5 min; the job itself checks the configured run_time setting
// and a per-day guard so it acts as a once-per-day trigger that respects
// admin-configured timing without requiring a deploy when changed.
Schedule::job(new DailyContentAgentJob)->everyFiveMinutes()->withoutOverlapping();

// GSC data lags ~2 days, so a daily run picks up freshly-available rows.
Schedule::command('gsc:sync')->dailyAt('06:00')->timezone('Africa/Lagos')->withoutOverlapping();
