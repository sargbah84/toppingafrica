<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Blade;

final class ContentShortcodeParser
{
    public function parse(string $content): string
    {
        return preg_replace_callback(
            '/\[poll:([a-z0-9\-]+)\]/',
            function (array $matches): string {
                $slug = $matches[1];
                $post = Post::where('slug', $slug)
                    ->where('post_type', 'poll')
                    ->published()
                    ->first();

                if (!$post) {
                    return '';
                }

                return Blade::render(
                    '<livewire:blog.poll-widget :post="$post" />',
                    ['post' => $post]
                );
            },
            $content
        );
    }
}
