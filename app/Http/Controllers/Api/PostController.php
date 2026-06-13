<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * External Blog Content API.
 *
 * Lets a trusted external site list, read, create and update blog posts.
 * Authenticated by the static bearer token in the `api.token` middleware.
 * All posts created/updated here are attributed to the system API user.
 */
class PostController extends Controller
{
    /** Statuses an external caller is allowed to set. */
    private const ALLOWED_STATUSES = ['draft', 'published', 'scheduled'];

    /**
     * GET /api/posts — paginated list with optional filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(self::ALLOWED_STATUSES)],
            'post_type' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $posts = Post::with(['author', 'categories', 'tags'])
            ->when($validated['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($validated['post_type'] ?? null, fn ($q, $t) => $q->where('post_type', $t))
            ->when($validated['search'] ?? null, fn ($q, $term) => $q->search($term))
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return PostResource::collection($posts);
    }

    /**
     * GET /api/posts/{post} — fetch a single post by slug.
     */
    public function show(Post $post): PostResource
    {
        $post->load(['author', 'categories', 'tags']);

        return new PostResource($post);
    }

    /**
     * POST /api/posts — create a new post.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $post = new Post(Arr::except($validated, $this->nonFillableKeys()));
        $post->author_id = $this->systemAuthorId();
        $this->applyStatus($post, $validated);
        $post->save();

        $this->syncCategories($post, $validated['categories'] ?? null);
        $this->syncTags($post, $validated['tags'] ?? null);

        $post->load(['author', 'categories', 'tags']);

        return (new PostResource($post))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    /**
     * PUT/PATCH /api/posts/{post} — update an existing post.
     *
     * PATCH semantics: only the fields present in the payload are changed.
     */
    public function update(Request $request, Post $post): PostResource
    {
        $validated = $this->validatePayload($request, updating: true, post: $post);

        $post->fill(Arr::except($validated, $this->nonFillableKeys()));

        if (array_key_exists('status', $validated)) {
            $this->applyStatus($post, $validated);
        }

        $post->save();

        // Only touch relations the caller actually sent, so a metadata-only
        // PATCH never wipes existing categories/tags.
        if (array_key_exists('categories', $validated)) {
            $this->syncCategories($post, $validated['categories']);
        }
        if (array_key_exists('tags', $validated)) {
            $this->syncTags($post, $validated['tags']);
        }

        $post->load(['author', 'categories', 'tags']);

        return new PostResource($post);
    }

    /**
     * Validation rules shared by store/update. On update, `required` fields
     * become `sometimes` so PATCH can send a partial payload.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $updating = false, ?Post $post = null): array
    {
        $req = $updating ? 'sometimes' : 'required';
        $slugUnique = Rule::unique('posts', 'slug')->ignore($post?->id);

        return $request->validate([
            'title' => [$req, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $slugUnique],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => [$req, 'string'],
            'featured_image' => ['nullable', 'string', 'max:500', 'url'],
            'post_type' => ['nullable', Rule::in(array_keys(config('blog.post_types', [])))],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['nullable', 'boolean'],
            'reading_time' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(self::ALLOWED_STATUSES)],
            'published_at' => ['nullable', 'date'],
            // Required only when scheduling, and must be in the future.
            'scheduled_at' => ['nullable', 'required_if:status,scheduled', 'date', 'after:now'],

            // Categories/tags accept arrays of id, slug, or name.
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
        ]);
    }

    /**
     * Keys excluded from mass assignment: relation arrays and status/timestamp
     * fields, which {@see applyStatus()} owns.
     *
     * @return array<int, string>
     */
    private function nonFillableKeys(): array
    {
        return ['categories', 'tags', 'status', 'published_at', 'scheduled_at'];
    }

    /**
     * Resolve and set status + the matching timestamp. Defaults to draft when
     * the caller omits status on create.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyStatus(Post $post, array $data): void
    {
        $status = $data['status'] ?? 'draft';
        $post->status = $status;

        if ($status === 'published') {
            $post->published_at = $data['published_at'] ?? now();
            $post->scheduled_at = null;
        } elseif ($status === 'scheduled') {
            // `scheduled_at` is validated as required-ish for scheduled posts.
            $post->scheduled_at = $data['scheduled_at'] ?? now()->addDay();
            $post->published_at = null;
        } else { // draft
            $post->published_at = null;
        }
    }

    /**
     * Sync categories. Each entry may be a numeric id, a slug, or a name.
     * Unknown names are NOT auto-created (categories are an editorial taxonomy);
     * unmatched entries are silently skipped.
     *
     * @param  array<int, string>|null  $entries
     */
    private function syncCategories(Post $post, ?array $entries): void
    {
        $ids = collect($entries ?? [])
            ->map(fn ($e) => $this->resolveCategoryId((string) $e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $post->categories()->sync($ids);
    }

    /**
     * Sync tags. Each entry may be a numeric id, a slug, or a name. Unknown
     * names ARE created on the fly (tags are free-form).
     *
     * @param  array<int, string>|null  $entries
     */
    private function syncTags(Post $post, ?array $entries): void
    {
        $ids = collect($entries ?? [])
            ->map(fn ($e) => $this->resolveTagId((string) $e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $post->tags()->sync($ids);
    }

    private function resolveCategoryId(string $entry): ?int
    {
        $entry = trim($entry);
        if ($entry === '') {
            return null;
        }

        if (ctype_digit($entry)) {
            return Category::whereKey((int) $entry)->value('id');
        }

        return Category::where('slug', Str::slug($entry))
            ->orWhere('name', $entry)
            ->value('id');
    }

    private function resolveTagId(string $entry): ?int
    {
        $entry = trim($entry);
        if ($entry === '') {
            return null;
        }

        if (ctype_digit($entry)) {
            return Tag::whereKey((int) $entry)->value('id');
        }

        return Tag::firstOrCreate(
            ['slug' => Str::slug($entry)],
            ['name' => $entry],
        )->id;
    }

    /**
     * Resolve (and lazily create) the dedicated system user that owns all
     * externally-created posts.
     */
    private function systemAuthorId(): int
    {
        $email = config('services.blog_api.author_email');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('services.blog_api.author_name'),
                'password' => bcrypt(Str::random(40)),
                'is_staff' => true,
                'email_verified_at' => now(),
            ],
        );

        return $user->id;
    }
}
