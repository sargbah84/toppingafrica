<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Creator;
use App\Models\MediaLibraryItem;
use App\Models\Post;
use App\Models\User;
use App\Services\Blog\LibraryImageMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class LibraryImageMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_query_has_no_usable_tokens(): void
    {
        $matcher = app(LibraryImageMatcher::class);

        $this->assertNull($matcher->findBest(''));
        $this->assertNull($matcher->findBest('the and for')); // stopwords only
        $this->assertNull($matcher->findBest('a b c'));       // all too short
    }

    public function test_returns_null_when_library_has_no_matching_media(): void
    {
        $this->seedLibraryMedia('cat-photo.jpg', ['alt_text' => 'A photo of a cat']);

        $matcher = app(LibraryImageMatcher::class);

        $this->assertNull($matcher->findBest('basketball game championship'));
    }

    public function test_matches_on_alt_text_tokens(): void
    {
        $burnaBoyMedia = $this->seedLibraryMedia(
            'burna-boy-concert.jpg',
            ['alt_text' => 'Burna Boy performing live at the Wembley concert']
        );
        $this->seedLibraryMedia('random.jpg', ['alt_text' => 'A random landscape photo']);

        $matcher = app(LibraryImageMatcher::class);

        $hit = $matcher->findBest('Burna Boy Wembley performance');

        $this->assertNotNull($hit);
        $this->assertSame($burnaBoyMedia->id, $hit['media']->id);
        $this->assertGreaterThan(0, $hit['score']);
    }

    public function test_high_confidence_threshold_separates_strong_from_weak_matches(): void
    {
        // Strong match: every distinguishing token from the query appears in alt_text.
        $this->seedLibraryMedia(
            'ngannou-fight.jpg',
            ['alt_text' => 'Francis Ngannou knockout victory at heavyweight title fight']
        );

        $matcher = app(LibraryImageMatcher::class);

        $strong = $matcher->findBest('Francis Ngannou knockout heavyweight title');
        $this->assertNotNull($strong);
        $this->assertGreaterThanOrEqual(
            LibraryImageMatcher::HIGH_CONFIDENCE_THRESHOLD,
            $strong['score_normalized'],
            'Strong overlap should pass the high-confidence threshold'
        );

        // Weak match: only one token of a multi-token query matches.
        // With 5 query tokens, max possible = 5*4 = 20, so a 3-weight alt
        // hit normalizes to 0.15 — well below threshold.
        $weak = $matcher->findBest('knockout streaming netflix lagos studio');
        if ($weak !== null) {
            $this->assertLessThan(
                LibraryImageMatcher::HIGH_CONFIDENCE_THRESHOLD,
                $weak['score_normalized'],
                'Single matching token within a multi-token query should NOT pass high-confidence threshold'
            );
        }
    }

    public function test_creator_profile_photo_scores_higher_than_generic_library_media(): void
    {
        // Library media that contains the name in its alt text.
        $this->seedLibraryMedia(
            'tems-cover.jpg',
            ['alt_text' => 'Tems album cover artwork release']
        );

        // Creator with profile photo — name "Tems" should outweigh the alt match.
        $tems = Creator::create([
            'name' => 'Tems',
            'bio' => 'Nigerian singer and songwriter.',
            'country' => 'Nigeria',
            'category' => 'Music',
            'status' => 'published',
        ]);
        $this->attachProfileImage($tems, 'tems-profile.jpg');

        $matcher = app(LibraryImageMatcher::class);

        $hit = $matcher->findBest('Tems Nigerian singer');

        $this->assertNotNull($hit);
        $this->assertSame('library_creator', $hit['source']);
    }

    public function test_excludes_already_used_media_ids(): void
    {
        $first = $this->seedLibraryMedia('first.jpg', ['alt_text' => 'Sarkodie hip hop Ghana legendary']);
        $second = $this->seedLibraryMedia('second.jpg', ['alt_text' => 'Sarkodie hip hop Ghana superstar']);

        $matcher = app(LibraryImageMatcher::class);

        $hit = $matcher->findBest('Sarkodie hip hop Ghana', excludeMediaIds: [$first->id]);

        $this->assertNotNull($hit);
        $this->assertSame($second->id, $hit['media']->id);
    }

    public function test_find_best_for_post_returns_attached_creator_photo_immediately(): void
    {
        $author = User::factory()->create();
        $creator = Creator::create([
            'name' => 'Davido',
            'bio' => 'Nigerian Afrobeats star.',
            'country' => 'Nigeria',
            'category' => 'Music',
            'status' => 'published',
        ]);
        $this->attachProfileImage($creator, 'davido.jpg');

        $post = Post::factory()->create([
            'title' => 'Some unrelated headline about technology',
            'author_id' => $author->id,
        ]);
        $post->creators()->sync([$creator->id]);

        $matcher = app(LibraryImageMatcher::class);

        $hit = $matcher->findBestForPost($post);

        $this->assertNotNull($hit);
        $this->assertSame('library_creator_attached', $hit['source']);
        $this->assertSame(1.0, $hit['score_normalized']);
    }

    private function seedLibraryMedia(string $filename, array $customProperties = []): Media
    {
        $user = User::factory()->create();
        $item = MediaLibraryItem::create([
            'user_id' => $user->id,
            'name' => $filename,
        ]);

        // Create a minimal valid JPEG (the 1×1 byte "magic header") so Spatie
        // accepts it. We never actually read the file in matcher tests.
        $tempPath = tempnam(sys_get_temp_dir(), 'lib_img_').'.jpg';
        file_put_contents($tempPath, base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AB//Z'
        ));

        $media = $item->addMedia($tempPath)
            ->usingFileName($filename)
            ->usingName(pathinfo($filename, PATHINFO_FILENAME))
            ->withCustomProperties($customProperties)
            ->toMediaCollection('default');

        return $media;
    }

    private function attachProfileImage(Creator $creator, string $filename): Media
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'profile_').'.jpg';
        file_put_contents($tempPath, base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AB//Z'
        ));

        return $creator->addMedia($tempPath)
            ->usingFileName($filename)
            ->toMediaCollection('profile_image');
    }
}
