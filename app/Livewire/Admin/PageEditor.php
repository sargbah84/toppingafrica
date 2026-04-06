<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PageEditor extends Component
{
    public ?int $pageId = null;

    public string $title = '';

    public string $slug = '';

    public string $content = '';

    public string $template = 'default';

    public ?int $linked_category_id = null;

    public ?int $linked_tag_id = null;

    public string $status = 'draft';

    public int $order = 0;

    public string $meta_title = '';

    public string $meta_description = '';

    public bool $autoGenerateSlug = true;

    public bool $showAiGenerator = false;

    public string $aiPrompt = '';

    public string $aiTone = 'professional';

    public function mount(?int $pageId = null): void
    {
        if ($pageId) {
            $this->pageId = $pageId;
            $this->loadPage($pageId);
        }
    }

    public function loadPage(int $pageId): void
    {
        $page = Page::findOrFail($pageId);
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->content = $page->content ?? '';
        $this->template = $page->template ?? 'default';
        $this->linked_category_id = $page->linked_category_id;
        $this->linked_tag_id = $page->linked_tag_id;
        $this->status = $page->status;
        $this->order = $page->order ?? 0;
        $this->meta_title = $page->meta_title ?? '';
        $this->meta_description = $page->meta_description ?? '';
        $this->autoGenerateSlug = false;
    }

    public function updatedTitle(): void
    {
        if ($this->autoGenerateSlug) {
            $this->slug = Str::slug($this->title);
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

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug' . ($this->pageId ? ',' . $this->pageId : '')],
            'content' => [$this->template === 'blog' ? 'nullable' : 'required', 'string'],
            'template' => ['nullable', 'string', 'max:100'],
            'linked_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'linked_tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'status' => ['required', 'in:draft,published'],
            'order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);

        if ($this->pageId) {
            $page = Page::findOrFail($this->pageId);
            $page->update($validated);
        } else {
            $page = Page::create($validated);
            $this->pageId = $page->id;
        }

        session()->flash('success', 'Page ' . ($this->pageId ? 'updated' : 'created') . ' successfully.');

        $this->redirect(route('admin.pages.edit', $page), navigate: true);
    }

    public function generateWithAi(): void
    {
        if (empty($this->aiPrompt)) {
            return;
        }

        $provider = config('blog.ai.providers.openai.enabled') ? 'openai' : (config('blog.ai.providers.perplexity.enabled') ? 'perplexity' : null);

        if (!$provider) {
            session()->flash('error', 'No AI provider configured.');
            return;
        }

        $providerConfig = config("blog.ai.providers.{$provider}");

        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteUrl = config('app.url');
        $siteDescription = \App\Models\Setting::get('site_description', '');

        $isBlogTemplate = $this->template === 'blog';

        if ($isBlogTemplate) {
            $systemPrompt = "You are a professional content writer and SEO expert writing for \"{$siteName}\" ({$siteUrl}). {$siteDescription}\n\nThis is a BLOG LISTING page that dynamically displays blog post cards. You only need to generate a short intro description (1-2 paragraphs max, in HTML) that appears above the post grid, plus SEO metadata. Do NOT write full article content — the page auto-populates with blog posts. Use the actual name \"{$siteName}\" — never placeholders. Write in a {$this->aiTone} tone.\n\nReturn your response as JSON with this exact structure:\n{\"content\": \"<short html intro>\", \"meta_title\": \"SEO title (max 60 chars)\", \"meta_description\": \"SEO description (max 155 chars)\"}\n\nReturn ONLY the JSON, no markdown fences.";
        } else {
            $systemPrompt = "You are a professional content writer and SEO expert writing for \"{$siteName}\" ({$siteUrl}). {$siteDescription}\n\nGenerate well-structured HTML content for a website page, plus SEO metadata. Use the actual company/website name \"{$siteName}\" wherever relevant — never use placeholders like [Company Name]. Use proper headings (h2, h3), paragraphs, and lists where appropriate. Do not include the page title as an h1 - start with the body content. Write in a {$this->aiTone} tone.\n\nReturn your response as JSON with this exact structure:\n{\"content\": \"<html content here>\", \"meta_title\": \"SEO title (max 60 chars)\", \"meta_description\": \"SEO description (max 155 chars)\"}\n\nReturn ONLY the JSON, no markdown fences.";
        }

        $userPrompt = $isBlogTemplate
            ? "Write a short intro description and SEO metadata for a blog listing page about: {$this->aiPrompt}"
            : "Write the content for a webpage about: {$this->aiPrompt}";

        if ($this->title) {
            $userPrompt .= "\n\nThe page title is: {$this->title}";
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($providerConfig['api_key'])
                ->timeout(60)
                ->post($provider === 'openai' ? 'https://api.openai.com/v1/chat/completions' : 'https://api.perplexity.ai/chat/completions', [
                    'model' => $providerConfig['model'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => $providerConfig['max_tokens'],
                ]);

            if ($response->successful()) {
                $raw = $response->json('choices.0.message.content', '');

                // Clean markdown code fences if present
                $raw = preg_replace('/^```(?:json|html)?\s*/i', '', $raw);
                $raw = preg_replace('/\s*```$/', '', $raw);

                // Try to parse as JSON with content + SEO fields
                $parsed = json_decode($raw, true);
                if ($parsed && isset($parsed['content'])) {
                    $this->content = $parsed['content'];
                    $this->meta_title = $parsed['meta_title'] ?? '';
                    $this->meta_description = $parsed['meta_description'] ?? '';
                } else {
                    // Fallback: treat entire response as HTML content
                    $this->content = $raw;
                }

                $this->showAiGenerator = false;

                // Track usage
                $inputTokens = $response->json('usage.prompt_tokens', 0);
                $outputTokens = $response->json('usage.completion_tokens', 0);

                \App\Models\AiUsageLog::create([
                    'user_id' => auth()->id(),
                    'provider' => $provider,
                    'model' => $providerConfig['model'],
                    'feature' => 'page-generation',
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'total_tokens' => $inputTokens + $outputTokens,
                    'estimated_cost' => \App\Models\AiUsageLog::calculateCost($provider, $providerConfig['model'], $inputTokens, $outputTokens),
                    'is_successful' => true,
                ]);

                $this->dispatch('content-updated');
            } else {
                session()->flash('error', 'AI generation failed: ' . $response->body());
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'AI generation failed: ' . $e->getMessage());
        }
    }

    private function siteContext(): string
    {
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $siteUrl = config('app.url');
        $tagline = \App\Models\Setting::get('site_tagline', '');
        $description = \App\Models\Setting::get('site_description', '');
        $email = \App\Models\Setting::get('contact_email', '');

        $context = "You are writing for \"{$siteName}\" ({$siteUrl}).";
        if ($tagline) {
            $context .= " Tagline: \"{$tagline}\".";
        }
        if ($description) {
            $context .= " About: {$description}";
        }
        if ($email) {
            $context .= " Contact: {$email}.";
        }
        $context .= " Always use the actual name \"{$siteName}\" and URL \"{$siteUrl}\" — never use placeholders like [Company Name], [Website Name], or [website URL].";

        return $context;
    }

    public function aiImproveContent(): void
    {
        $this->callAiInline(
            $this->siteContext() . ' You are an expert editor. Improve the following HTML page content: make it more engaging, fix grammar, improve structure, and ensure proper HTML formatting. Keep the same general meaning and structure. Return only the improved HTML.',
            $this->content,
            'content',
            'page-improve',
        );
    }

    public function aiRewriteContent(): void
    {
        $this->callAiInline(
            $this->siteContext() . ' You are a professional content writer. Rewrite the following HTML page content in a fresh way while preserving the core information and meaning. Use proper HTML headings (h2, h3), paragraphs, and lists. Return only HTML.',
            $this->content,
            'content',
            'page-rewrite',
        );
    }

    public function aiGenerateSeo(): void
    {
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $this->callAiInline(
            "You are an SEO expert writing for \"{$siteName}\". Based on the page title and content provided, generate an optimized meta title (max 60 chars) and meta description (max 155 chars) using the brand name \"{$siteName}\". Return as JSON: {\"meta_title\": \"...\", \"meta_description\": \"...\"}. Return ONLY the JSON, no markdown.",
            "Title: {$this->title}\n\nContent: " . strip_tags(substr($this->content, 0, 2000)),
            'seo',
            'page-seo',
        );
    }

    private function callAiInline(string $systemPrompt, string $userContent, string $target, string $feature): void
    {
        if (empty($userContent)) {
            return;
        }

        $provider = config('blog.ai.providers.openai.enabled') ? 'openai' : (config('blog.ai.providers.perplexity.enabled') ? 'perplexity' : null);

        if (!$provider) {
            session()->flash('error', 'No AI provider configured.');
            return;
        }

        $providerConfig = config("blog.ai.providers.{$provider}");

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($providerConfig['api_key'])
                ->timeout(60)
                ->post($provider === 'openai' ? 'https://api.openai.com/v1/chat/completions' : 'https://api.perplexity.ai/chat/completions', [
                    'model' => $providerConfig['model'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_tokens' => $providerConfig['max_tokens'],
                ]);

            if ($response->successful()) {
                $result = $response->json('choices.0.message.content', '');
                $result = preg_replace('/^```(?:html|json)?\s*/i', '', $result);
                $result = preg_replace('/\s*```$/', '', $result);

                if ($target === 'seo') {
                    $seo = json_decode($result, true);
                    if ($seo) {
                        $this->meta_title = $seo['meta_title'] ?? $this->meta_title;
                        $this->meta_description = $seo['meta_description'] ?? $this->meta_description;
                    }
                } else {
                    $this->content = $result;
                    $this->dispatch('content-updated');
                }

                // Track usage
                $inputTokens = $response->json('usage.prompt_tokens', 0);
                $outputTokens = $response->json('usage.completion_tokens', 0);
                \App\Models\AiUsageLog::create([
                    'user_id' => auth()->id(),
                    'provider' => $provider,
                    'model' => $providerConfig['model'],
                    'feature' => $feature,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'total_tokens' => $inputTokens + $outputTokens,
                    'estimated_cost' => \App\Models\AiUsageLog::calculateCost($provider, $providerConfig['model'], $inputTokens, $outputTokens),
                    'is_successful' => true,
                ]);
            } else {
                session()->flash('error', 'AI request failed.');
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'AI request failed: ' . $e->getMessage());
        }
    }

    #[On('media-selected')]
    public function onMediaSelected(string $url, string $context = ''): void
    {
        if ($context === 'content_image') {
            $this->dispatch('insert-content-image', url: $url);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.page-editor');
    }
}
