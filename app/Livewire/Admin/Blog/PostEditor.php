<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PostEditor extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $postId = null;

    // Core fields
    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $content = '';

    // Post type
    public string $post_type = 'article';

    public array $type_data = [];

    // Taxonomy
    /** @var array<int> */
    public array $selectedCategories = [];

    public string $tagInput = '';

    /** @var array<string> */
    public array $selectedTags = [];

    // Media
    public $featuredImage = null;

    public ?string $existingFeaturedImageUrl = null;

    // SEO
    public string $meta_title = '';

    public string $meta_description = '';

    public string $focus_keyword = '';

    // Social / OG
    public array $og_meta = [
        'og_title' => '',
        'og_description' => '',
        'og_image' => '',
        'twitter_card' => 'summary_large_image',
    ];

    // Status
    public string $status = 'draft';

    public ?string $scheduled_at = null;

    public bool $is_featured = false;

    // UI state
    public bool $showSeoPanel = false;

    public bool $showSocialPanel = false;

    public bool $autoGenerateSlug = true;

    /** @var array<string> */
    public array $postTypes = [
        'article',
        'video',
        'quiz',
        'trivia',
        'listicle',
        'gallery',
        'poll',
    ];

    public function mount(?int $postId = null): void
    {
        $this->postId = $postId;

        if ($postId) {
            $this->loadPost($postId);
        } else {
            $this->loadFromSession();
        }
    }

    public function loadPost(int $postId): void
    {
        $post = Post::with(['categories', 'tags'])->findOrFail($postId);

        $this->postId = $post->id;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->excerpt = $post->excerpt ?? '';
        $this->content = $post->content ?? '';
        $this->post_type = $post->post_type ?? 'article';
        $this->type_data = $post->type_data ?? [];
        $this->selectedCategories = $post->categories->pluck('id')->toArray();
        $this->selectedTags = $post->tags->pluck('name')->toArray();
        $this->meta_title = $post->meta_title ?? '';
        $this->meta_description = $post->meta_description ?? '';
        $this->focus_keyword = $post->focus_keyword ?? '';
        $this->og_meta = array_merge($this->og_meta, $post->og_meta ?? []);
        $this->status = $post->status;
        $this->scheduled_at = $post->scheduled_at?->format('Y-m-d\TH:i');
        $this->is_featured = $post->is_featured;
        $this->existingFeaturedImageUrl = $post->featured_image_url;
        $this->autoGenerateSlug = false;
    }

    public function loadFromSession(): void
    {
        $sessionData = session()->pull('ai_generated_post');

        if (! $sessionData) {
            return;
        }

        $this->title = $sessionData['title'] ?? '';
        $this->content = $sessionData['content'] ?? '';
        $this->excerpt = $sessionData['excerpt'] ?? '';
        $this->post_type = $sessionData['post_type'] ?? 'article';
        $this->type_data = $sessionData['type_data'] ?? [];
        $this->meta_title = $sessionData['meta_title'] ?? '';
        $this->meta_description = $sessionData['meta_description'] ?? '';
        $this->focus_keyword = $sessionData['focus_keyword'] ?? '';
        $this->selectedTags = $sessionData['tags'] ?? [];

        if (! empty($this->title)) {
            $this->slug = Str::slug($this->title);
        }
    }

    #[On('use-generated-content')]
    public function useGeneratedContent(
        string $title = '',
        string $content = '',
        string $excerpt = '',
        string $meta_title = '',
        string $meta_description = '',
        string $focus_keyword = '',
        string $post_type = 'article',
        array $type_data = [],
        array $tags = [],
    ): void {
        if ($title !== '') {
            $this->title = $title;
            $this->slug = Str::slug($title);
        }
        if ($content !== '') {
            $this->content = $content;
        }
        if ($excerpt !== '') {
            $this->excerpt = $excerpt;
        }
        if ($meta_title !== '') {
            $this->meta_title = $meta_title;
        }
        if ($meta_description !== '') {
            $this->meta_description = $meta_description;
        }
        if ($focus_keyword !== '') {
            $this->focus_keyword = $focus_keyword;
        }
        if ($post_type !== 'article') {
            $this->post_type = $post_type;
        }
        if (! empty($type_data)) {
            $this->type_data = $type_data;
        }
        if (! empty($tags)) {
            $this->selectedTags = array_unique(array_merge($this->selectedTags, $tags));
        }

        $this->dispatch('notify', type: 'success', message: 'AI-generated content loaded.');
        $this->dispatch('content-updated');
    }

    public function updatedTitle(string $value): void
    {
        if ($this->autoGenerateSlug) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedPostType(): void
    {
        $this->type_data = $this->getDefaultTypeData($this->post_type);
    }

    public function generateSlug(): void
    {
        $this->slug = Str::slug($this->title);
    }

    public function addTag(): void
    {
        $tag = trim($this->tagInput);

        if ($tag !== '' && ! in_array($tag, $this->selectedTags, true)) {
            $this->selectedTags[] = $tag;
        }

        $this->tagInput = '';
    }

    public function removeTag(int $index): void
    {
        unset($this->selectedTags[$index]);
        $this->selectedTags = array_values($this->selectedTags);
    }

    public function removeFeaturedImage(): void
    {
        $this->featuredImage = null;
        $this->existingFeaturedImageUrl = null;
    }

    #[On('media-selected')]
    public function onMediaSelected(string $url, string $context): void
    {
        if ($context === 'featured_image') {
            $this->existingFeaturedImageUrl = $url;
            $this->featuredImage = null;
        } elseif ($context === 'content_image') {
            $this->dispatch('insert-content-image', url: $url);
        }
    }

    public function saveDraft(): void
    {
        $this->status = 'draft';
        $this->save();
    }

    public function publish(): void
    {
        $this->status = 'published';
        $this->save();
    }

    public function schedule(): void
    {
        $this->status = 'scheduled';
        $this->save();
    }

    public function save(): void
    {
        $validated = $this->validate();

        $postData = [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt ?: null,
            'content' => $this->content,
            'post_type' => $this->post_type,
            'type_data' => $this->type_data ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'focus_keyword' => $this->focus_keyword ?: null,
            'og_meta' => $this->og_meta,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'reading_time' => $this->calculateReadingTime(),
        ];

        if ($this->status === 'published' && ! $this->postId) {
            $postData['published_at'] = now();
        } elseif ($this->status === 'published') {
            $postData['published_at'] = $postData['published_at'] ?? now();
        }

        if ($this->status === 'scheduled' && $this->scheduled_at) {
            $postData['scheduled_at'] = $this->scheduled_at;
            $postData['published_at'] = null;
        }

        if ($this->postId) {
            $post = Post::findOrFail($this->postId);
            $post->update($postData);
        } else {
            $postData['author_id'] = auth()->id();
            $post = Post::create($postData);
            $this->postId = $post->id;
            $this->autoGenerateSlug = false;
        }

        // Sync categories
        $post->categories()->sync($this->selectedCategories);

        // Sync tags (create if not exists)
        $tagIds = collect($this->selectedTags)->map(function (string $tagName): int {
            return Tag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => $tagName]
            )->id;
        })->toArray();
        $post->tags()->sync($tagIds);

        // Handle featured image upload
        if ($this->featuredImage) {
            $post->clearMediaCollection('featured_image');
            $post->addMedia($this->featuredImage->getRealPath())
                ->usingFileName(Str::slug($this->title).'.'.$this->featuredImage->getClientOriginalExtension())
                ->toMediaCollection('featured_image');
            $this->existingFeaturedImageUrl = $post->fresh()->featured_image_url;
            $this->featuredImage = null;
        }

        $message = $this->status === 'published'
            ? 'Post published successfully.'
            : ($this->status === 'scheduled' ? 'Post scheduled successfully.' : 'Draft saved.');

        $this->dispatch('notify', type: 'success', message: $message);
        $this->dispatch('post-saved', postId: $post->id);
    }

    #[Computed]
    public function availableCategories(): Collection
    {
        return Category::query()
            ->active()
            ->ordered()
            ->with('children')
            ->topLevel()
            ->get();
    }

    #[Computed]
    public function availableTags(): Collection
    {
        return Tag::orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.blog.post-editor');
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($this->postId),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => in_array($this->post_type, ['quiz', 'trivia', 'poll'])
                ? ['nullable', 'string']
                : ['required', 'string', 'min:10'],
            'post_type' => ['required', Rule::in($this->postTypes)],
            'type_data' => ['nullable', 'array'],
            'selectedCategories' => ['array'],
            'selectedCategories.*' => ['integer', 'exists:categories,id'],
            'selectedTags' => ['array'],
            'selectedTags.*' => ['string', 'max:50'],
            'featuredImage' => ['nullable', 'image', 'max:5120'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'og_meta' => ['nullable', 'array'],
            'og_meta.og_title' => ['nullable', 'string', 'max:100'],
            'og_meta.og_description' => ['nullable', 'string', 'max:200'],
            'og_meta.og_image' => ['nullable', 'string', 'max:500'],
            'og_meta.twitter_card' => ['nullable', 'string', Rule::in(['summary', 'summary_large_image'])],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'scheduled_at' => ['nullable', 'required_if:status,scheduled', 'date', 'after:now'],
            'is_featured' => ['boolean'],
        ];

        if ($this->post_type === 'quiz') {
            $rules['type_data.questions'] = ['required', 'array', 'min:1'];
            $rules['type_data.questions.*.question'] = ['required', 'string', 'min:3'];
            $rules['type_data.questions.*.answers'] = ['required', 'array', 'min:2'];
            $rules['type_data.questions.*.answers.*.text'] = ['required', 'string'];
            $rules['type_data.passing_score'] = ['required', 'integer', 'min:0', 'max:100'];
        }

        if ($this->post_type === 'poll') {
            $rules['type_data.options'] = ['required', 'array', 'min:2'];
            $rules['type_data.options.*.text'] = ['required', 'string', 'min:1'];
            $rules['type_data.ends_at'] = ['nullable', 'date'];
        }

        if ($this->post_type === 'trivia') {
            $rules['type_data.facts'] = ['required', 'array', 'min:1'];
            $rules['type_data.facts.*.text'] = ['required', 'string', 'min:3'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'A post title is required.',
            'content.required' => 'Post content cannot be empty.',
            'scheduled_at.required_if' => 'A schedule date is required when scheduling a post.',
            'scheduled_at.after' => 'The schedule date must be in the future.',
        ];
    }

    protected function getDefaultTypeData(string $type): array
    {
        return match ($type) {
            'video' => [
                'video_url' => '',
                'video_platform' => 'youtube',
                'duration' => '',
            ],
            'quiz' => [
                'questions' => [],
                'passing_score' => 70,
                'show_answers' => true,
            ],
            'trivia' => [
                'facts' => [],
                'source_url' => '',
            ],
            'listicle' => [
                'items' => [],
                'numbered' => true,
            ],
            'gallery' => [
                'images' => [],
                'layout' => 'grid',
                'columns' => 3,
            ],
            'poll' => [
                'options' => [],
                'allow_multiple' => false,
                'show_results_before_vote' => false,
                'ends_at' => null,
            ],
            default => [],
        };
    }

    // Quiz type_data methods

    public function addQuizQuestion(): void
    {
        $this->type_data['questions'][] = [
            'question' => '',
            'answers' => [
                ['text' => '', 'is_correct' => true],
                ['text' => '', 'is_correct' => false],
            ],
            'explanation' => '',
        ];
    }

    public function removeQuizQuestion(int $index): void
    {
        unset($this->type_data['questions'][$index]);
        $this->type_data['questions'] = array_values($this->type_data['questions']);
    }

    public function addQuizAnswer(int $questionIndex): void
    {
        $this->type_data['questions'][$questionIndex]['answers'][] = [
            'text' => '',
            'is_correct' => false,
        ];
    }

    public function removeQuizAnswer(int $questionIndex, int $answerIndex): void
    {
        unset($this->type_data['questions'][$questionIndex]['answers'][$answerIndex]);
        $this->type_data['questions'][$questionIndex]['answers'] = array_values(
            $this->type_data['questions'][$questionIndex]['answers']
        );
    }

    public function setCorrectAnswer(int $questionIndex, int $answerIndex): void
    {
        foreach ($this->type_data['questions'][$questionIndex]['answers'] as $i => &$answer) {
            $answer['is_correct'] = ($i === $answerIndex);
        }
    }

    // Trivia type_data methods

    public function addTriviaFact(): void
    {
        $this->type_data['facts'][] = ['text' => '', 'source' => ''];
    }

    public function removeTriviaFact(int $index): void
    {
        unset($this->type_data['facts'][$index]);
        $this->type_data['facts'] = array_values($this->type_data['facts']);
    }

    // Poll type_data methods

    public function addPollOption(): void
    {
        $this->type_data['options'][] = ['text' => ''];
    }

    public function removePollOption(int $index): void
    {
        unset($this->type_data['options'][$index]);
        $this->type_data['options'] = array_values($this->type_data['options']);
    }

    protected function calculateReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));

        return max(1, (int) ceil($wordCount / 200));
    }
}
