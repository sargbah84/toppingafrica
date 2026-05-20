<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Setting;
use App\Models\User;
use App\Services\Blog\ContentAgentService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\Models\Activity;

class DailyContentAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 1;

    public function handle(ContentAgentService $agent): void
    {
        if (! $agent->isEnabled()) {
            Log::info('DailyContentAgentJob: skipped (agent disabled)');

            return;
        }

        $config = $agent->config();

        // Only run when the current Lagos clock matches the configured run time
        // (within the 5-min cron tick), and only once per Lagos calendar day.
        $now = CarbonImmutable::now('Africa/Lagos');
        [$targetHour, $targetMinute] = array_pad(explode(':', $config['run_time']), 2, '0');
        $targetToday = $now->setTime((int) $targetHour, (int) $targetMinute, 0);

        if ($now->lt($targetToday) || $now->gt($targetToday->addMinutes(5))) {
            return;
        }

        $today = $now->toDateString();
        if (Setting::get('content_agent_last_run_date') === $today) {
            return;
        }
        Setting::set('content_agent_last_run_date', $today);

        $target = max(1, (int) $config['posts_per_day']);

        $ideas = $agent->pickIdeas($target);

        if ($ideas->isEmpty()) {
            Log::info('DailyContentAgentJob: no eligible ideas');
            Setting::set('content_agent_last_run_summary', $this->summary(0, 0, 'no_ideas'));

            return;
        }

        $authorId = $this->resolveAuthorId();
        // Deterministic so dry-run preview in the calendar UI accurately
        // predicts what this run will produce. Seed is the calendar date,
        // so different days still produce different (but stable) timing.
        $slots = $agent->buildSlots($ideas->count(), CarbonImmutable::now('Africa/Lagos'), deterministic: true);

        $dispatched = 0;
        foreach ($ideas->values() as $i => $idea) {
            if (! isset($slots[$i])) {
                break;
            }

            ProcessIdeaJob::dispatch(
                $idea->id,
                $slots[$i]->toDateTimeString(),
                $authorId,
            );

            $dispatched++;
        }

        Setting::set('content_agent_last_run_at', now()->toDateTimeString());
        Setting::set('content_agent_last_run_summary', $this->summary($ideas->count(), $dispatched, 'ok'));

        activity('content_agent')
            ->event('daily_run')
            ->withProperties([
                'ideas_picked' => $ideas->count(),
                'dispatched' => $dispatched,
                'first_slot' => $slots[0]?->toDateTimeString(),
                'last_slot' => isset($slots[count($slots) - 1]) ? $slots[count($slots) - 1]->toDateTimeString() : null,
            ])
            ->log("Daily run: picked {$ideas->count()} ideas, dispatched {$dispatched} jobs");

        Log::info('DailyContentAgentJob: dispatched per-idea jobs', [
            'ideas_picked' => $ideas->count(),
            'dispatched' => $dispatched,
            'first_slot' => $slots[0] ?? null,
            'last_slot' => $slots[count($slots) - 1] ?? null,
        ]);
    }

    private function resolveAuthorId(): ?int
    {
        return User::query()
            ->where('is_super_admin', true)
            ->orderBy('id')
            ->value('id')
            ?? User::query()->where('is_staff', true)->orderBy('id')->value('id');
    }

    private function summary(int $picked, int $dispatched, string $status): string
    {
        return json_encode([
            'status' => $status,
            'ideas_picked' => $picked,
            'dispatched' => $dispatched,
            'ran_at' => now()->toDateTimeString(),
        ]);
    }
}
