<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(): void
    {
        $posts = Post::readyToPublish()->get();

        if ($posts->isEmpty()) {
            return;
        }

        $published = 0;
        $failed = 0;

        foreach ($posts as $post) {
            try {
                $post->publish();
                $published++;
            } catch (Throwable $e) {
                $failed++;
                Log::error('PublishScheduledPostsJob: Failed to publish post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('PublishScheduledPostsJob: Completed', [
            'candidates' => $posts->count(),
            'published' => $published,
            'failed' => $failed,
        ]);
    }
}
