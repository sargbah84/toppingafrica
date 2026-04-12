<?php

use App\Jobs\CleanupExpiredTrendsJob;
use App\Jobs\FetchAfricaTrendsJob;
use App\Jobs\ResearchContentIdeasJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduler:heartbeat')->everyMinute();

Schedule::job(new FetchAfricaTrendsJob)->dailyAt('07:00')->timezone('Africa/Lagos');
Schedule::job(new ResearchContentIdeasJob)->dailyAt('08:00')->timezone('Africa/Lagos');
Schedule::job(new CleanupExpiredTrendsJob)->dailyAt('00:00')->timezone('Africa/Lagos');
