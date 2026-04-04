<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateBotbleData extends Command
{
    protected $signature = 'migrate:botble {--fresh : Truncate local tables before importing} {--clean-shortcodes : Only clean Botble shortcodes from existing posts}';
    protected $description = 'Migrate data from the old Botble CMS database to the new schema';

    private string $conn = 'botble';

    public function handle(): int
    {
        if ($this->option('clean-shortcodes')) {
            $this->cleanBotbleShortcodes();
            return self::SUCCESS;
        }

        $this->info('Starting Botble data migration...');

        if ($this->option('fresh')) {
            $this->warn('Truncating local tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Post::truncate();
            Category::truncate();
            Tag::truncate();
            Comment::truncate();
            DB::table('category_post')->truncate();
            DB::table('post_tag')->truncate();
            Ad::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->migrateUsers();
        $this->migrateCategories();
        $this->migrateTags();
        $this->migratePosts();
        $this->migratePostRelations();
        $this->migrateComments();
        $this->migrateAds();
        $this->cleanBotbleShortcodes();

        $this->newLine();
        $this->info('Migration complete!');

        return self::SUCCESS;
    }

    private function migrateUsers(): void
    {
        $this->info('Migrating users...');

        $oldUsers = DB::connection($this->conn)->table('users')->get();
        $bar = $this->output->createProgressBar($oldUsers->count());

        foreach ($oldUsers as $old) {
            User::updateOrCreate(
                ['email' => $old->email],
                [
                    'name' => trim(($old->first_name ?? '') . ' ' . ($old->last_name ?? '')) ?: 'Admin',
                    'username' => $old->username,
                    'password' => $old->password ?? bcrypt('password'),
                    'is_super_admin' => (bool) $old->super_user,
                    'is_staff' => true,
                    'email_verified_at' => $old->email_verified_at ?? now(),
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Migrated {$oldUsers->count()} users.");
    }

    private function migrateCategories(): void
    {
        $this->info('Migrating categories...');

        $oldCategories = DB::connection($this->conn)->table('categories')
            ->where('status', 'published')
            ->orderBy('order')
            ->get();

        $slugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Category')
            ->pluck('key', 'reference_id');

        $bar = $this->output->createProgressBar($oldCategories->count());
        $idMap = [];

        foreach ($oldCategories as $old) {
            $slug = $slugs[$old->id] ?? Str::slug($old->name);

            $category = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $old->name,
                    'description' => $old->description,
                    'icon' => $old->icon,
                    'order' => $old->order,
                    'is_active' => true,
                    'parent_id' => null, // will fix in second pass
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]
            );

            $idMap[$old->id] = ['new_id' => $category->id, 'old_parent' => $old->parent_id];
            $bar->advance();
        }

        // Second pass: fix parent_id references
        foreach ($idMap as $oldId => $data) {
            if ($data['old_parent'] && isset($idMap[$data['old_parent']])) {
                Category::where('id', $data['new_id'])
                    ->update(['parent_id' => $idMap[$data['old_parent']]['new_id']]);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Migrated {$oldCategories->count()} categories.");
    }

    private function migrateTags(): void
    {
        $this->info('Migrating tags...');

        $oldTags = DB::connection($this->conn)->table('tags')
            ->where('status', 'published')
            ->get();

        $slugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Tag')
            ->pluck('key', 'reference_id');

        $bar = $this->output->createProgressBar($oldTags->count());

        foreach ($oldTags as $old) {
            $slug = $slugs[$old->id] ?? Str::slug($old->name);

            Tag::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $old->name,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Migrated {$oldTags->count()} tags.");
    }

    private function migratePosts(): void
    {
        $this->info('Migrating posts...');

        $oldPosts = DB::connection($this->conn)->table('posts')->get();

        $slugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Post')
            ->pluck('key', 'reference_id');

        // Map old user IDs to new user IDs by email
        $oldUsers = DB::connection($this->conn)->table('users')->pluck('email', 'id');
        $newUsers = User::pluck('id', 'email');

        $bar = $this->output->createProgressBar($oldPosts->count());

        foreach ($oldPosts as $old) {
            $slug = $slugs[$old->id] ?? Str::slug($old->name);
            $authorEmail = $oldUsers[$old->author_id] ?? null;
            $authorId = $authorEmail ? ($newUsers[$authorEmail] ?? 1) : 1;

            // Map Botble status to our status
            $status = match ($old->status) {
                'published' => 'published',
                'draft' => 'draft',
                'pending' => 'draft',
                default => 'draft',
            };

            // Calculate reading time
            $wordCount = str_word_count(strip_tags($old->content ?? ''));
            $readingTime = max(1, (int) ceil($wordCount / 200));

            Post::updateOrCreate(
                ['slug' => $slug],
                [
                    'author_id' => $authorId,
                    'title' => $old->name,
                    'excerpt' => $old->description,
                    'content' => $this->convertShortcodes($old->content ?? ''),
                    'featured_image' => $old->image,
                    'post_type' => 'article',
                    'status' => $status,
                    'published_at' => $status === 'published' ? $old->created_at : null,
                    'views_count' => $old->views ?? 0,
                    'reading_time' => $readingTime,
                    'is_featured' => (bool) $old->is_featured,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Migrated {$oldPosts->count()} posts.");
    }

    private function migratePostRelations(): void
    {
        $this->info('Migrating post-category and post-tag relationships...');

        // Build slug-to-ID maps for the new database
        $postSlugMap = Post::pluck('id', 'slug')->toArray();
        $catSlugMap = Category::pluck('id', 'slug')->toArray();
        $tagSlugMap = Tag::pluck('id', 'slug')->toArray();

        // Build old ID to slug maps
        $postSlugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Post')
            ->pluck('key', 'reference_id');
        $catSlugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Category')
            ->pluck('key', 'reference_id');
        $tagSlugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Tag')
            ->pluck('key', 'reference_id');

        // Post-Category relations
        $postCategories = DB::connection($this->conn)->table('post_categories')->get();
        $catRelations = 0;
        foreach ($postCategories as $pc) {
            $postSlug = $postSlugs[$pc->post_id] ?? null;
            $catSlug = $catSlugs[$pc->category_id] ?? null;

            if ($postSlug && $catSlug && isset($postSlugMap[$postSlug]) && isset($catSlugMap[$catSlug])) {
                DB::table('category_post')->insertOrIgnore([
                    'post_id' => $postSlugMap[$postSlug],
                    'category_id' => $catSlugMap[$catSlug],
                ]);
                $catRelations++;
            }
        }

        // Post-Tag relations
        $postTags = DB::connection($this->conn)->table('post_tags')->get();
        $tagRelations = 0;
        foreach ($postTags as $pt) {
            $postSlug = $postSlugs[$pt->post_id] ?? null;
            $tagSlug = $tagSlugs[$pt->tag_id] ?? null;

            if ($postSlug && $tagSlug && isset($postSlugMap[$postSlug]) && isset($tagSlugMap[$tagSlug])) {
                DB::table('post_tag')->insertOrIgnore([
                    'post_id' => $postSlugMap[$postSlug],
                    'tag_id' => $tagSlugMap[$tagSlug],
                ]);
                $tagRelations++;
            }
        }

        $this->info("  Migrated {$catRelations} post-category and {$tagRelations} post-tag relations.");
    }

    private function migrateAds(): void
    {
        $this->info('Migrating ads...');

        $oldAds = DB::connection($this->conn)->table('ads')->get();
        $bar = $this->output->createProgressBar($oldAds->count());

        foreach ($oldAds as $old) {
            $code = $old->google_adsense_slot_id ?? null;

            Ad::updateOrCreate(
                ['name' => $old->name],
                [
                    'position' => $old->key ?? 'sidebar',
                    'code' => $code,
                    'image' => $old->image ?? null,
                    'url' => $old->url ?? null,
                    'is_active' => $old->status === 'published',
                    'order' => $old->order ?? 0,
                    'starts_at' => null,
                    'ends_at' => $old->expired_at ?? null,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Migrated {$oldAds->count()} ads.");
    }

    private function migrateComments(): void
    {
        $this->info('Migrating comments...');

        $oldComments = DB::connection($this->conn)->table('fob_comments')
            ->where('reference_type', 'Botble\\Blog\\Models\\Post')
            ->get();

        $postSlugs = DB::connection($this->conn)->table('slugs')
            ->where('reference_type', 'Botble\\Blog\\Models\\Post')
            ->pluck('key', 'reference_id');
        $postSlugMap = Post::pluck('id', 'slug')->toArray();

        $bar = $this->output->createProgressBar($oldComments->count());
        $idMap = [];

        foreach ($oldComments as $old) {
            $postSlug = $postSlugs[$old->reference_id] ?? null;
            $newPostId = $postSlug ? ($postSlugMap[$postSlug] ?? null) : null;

            if (!$newPostId) {
                $bar->advance();
                continue;
            }

            $comment = Comment::create([
                'post_id' => $newPostId,
                'parent_id' => null, // fix in second pass
                'guest_name' => $old->name,
                'guest_email' => $old->email,
                'body' => $old->content,
                'status' => $old->status === 'approved' ? 'approved' : $old->status,
                'ip_address' => $old->ip_address,
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
            ]);

            $idMap[$old->id] = $comment->id;
            $bar->advance();
        }

        // Second pass: fix parent_id for replies
        foreach ($oldComments as $old) {
            if ($old->reply_to && isset($idMap[$old->id]) && isset($idMap[$old->reply_to])) {
                Comment::where('id', $idMap[$old->id])
                    ->update(['parent_id' => $idMap[$old->reply_to]]);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Migrated " . count($idMap) . " comments.");
    }

    private function cleanBotbleShortcodes(): void
    {
        $this->info('Cleaning Botble shortcodes from post content...');

        $posts = Post::where('content', 'like', '%[custom-html%')
            ->orWhere('content', 'like', '%[media %')
            ->orWhere('content', 'like', '%[content-quote%')
            ->orWhere('content', 'like', '%&lt;%')
            ->get();

        $bar = $this->output->createProgressBar($posts->count());

        foreach ($posts as $post) {
            $cleaned = $this->convertShortcodes($post->content);
            if ($cleaned !== $post->content) {
                $post->update(['content' => $cleaned]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Cleaned shortcodes in {$posts->count()} posts.");
    }

    private function convertShortcodes(string $content): string
    {
        // [custom-html enable_caching="yes"]...[/custom-html] → extract and decode inner HTML
        $content = preg_replace_callback(
            '/\[custom-html[^\]]*\](.*?)\[\/custom-html\]/s',
            fn ($m) => html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $content
        );

        // [media url="..." ...][/media] or [media url="..." ...] → YouTube embed
        $content = preg_replace_callback(
            '/\[media\s+url="([^"]+)"[^\]]*\](?:\s*\[\/media\])?/s',
            function ($matches) {
                $url = $matches[1];
                $videoId = null;

                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m)) {
                    $videoId = $m[1];
                }

                if ($videoId) {
                    return '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;"><iframe src="https://www.youtube.com/embed/' . $videoId . '" style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" allowfullscreen></iframe></div>';
                }

                return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
            },
            $content
        );

        // [content-quote content_text="..."][/content-quote] → blockquote
        $content = preg_replace_callback(
            '/\[content-quote\s+content_text="([^"]+)"\s*\]\s*\[\/content-quote\]/s',
            function ($matches) {
                return '<blockquote><p>' . $matches[1] . '</p></blockquote>';
            },
            $content
        );

        // Decode HTML entities left over from Botble's custom-html shortcode content
        if (str_contains($content, '&lt;')) {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Strip <script> tags (from social embed widgets) — they break editors
        $content = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $content);

        // Clean orphan shortcode tags
        $content = str_replace('</shortcode>', '', $content);

        return $content;
    }
}
