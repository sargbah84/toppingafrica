<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GoogleSearchConsoleService;
use Illuminate\Console\Command;

class SyncSearchConsoleData extends Command
{
    protected $signature = 'gsc:sync {--days=28 : Number of days to fetch}';

    protected $description = 'Sync Google Search Console data';

    public function handle(): int
    {
        $service = GoogleSearchConsoleService::fromConfig();

        if (!$service) {
            $this->error('Google Search Console is not configured. Set credentials in Settings > Search Console.');

            return self::FAILURE;
        }

        $days = (int) $this->option('days');
        $this->info("Fetching GSC data for the last {$days} days...");

        if ($service->fetchAndStore($days)) {
            $this->info('Search Console data synced successfully.');

            return self::SUCCESS;
        }

        $this->error('Failed to sync Search Console data. Check logs for details.');

        return self::FAILURE;
    }
}
