<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Creator;
use App\Services\CreatorEnricher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Enriches a creator that was auto-discovered from a generated post body —
 * generating a bio, fetching an avatar, and building social links in the
 * background so post generation itself stays fast.
 *
 * Carries the creator ID (not the model) so a deleted creator between
 * dispatch and run is handled gracefully rather than crashing on a stale
 * serialized model.
 */
class EnrichCreatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(
        public int $creatorId,
    ) {}

    public function handle(CreatorEnricher $enricher): void
    {
        $creator = Creator::find($this->creatorId);
        if ($creator === null) {
            return;
        }

        try {
            $enricher->enrich($creator, [
                'name' => $creator->name,
                'category' => $creator->category,
                'country' => $creator->country,
            ]);
        } catch (\Throwable $e) {
            Log::error('EnrichCreatorJob: enrichment failed', [
                'creator_id' => $this->creatorId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // let the queue retry per $tries
        }
    }
}
