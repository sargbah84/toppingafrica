<?php

declare(strict_types=1);

namespace App\Services\Blog\Seo;

use App\Models\Post;
use App\Models\SeoAnalysis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class SeoIntelligenceService
{
    protected array $config;

    public function __construct(
        private readonly ContentQualityAnalyzer $contentQualityAnalyzer,
        private readonly TechnicalSeoAnalyzer $technicalSeoAnalyzer,
        private readonly ReadabilityAnalyzer $readabilityAnalyzer,
        private readonly UserEngagementAnalyzer $userEngagementAnalyzer,
        private readonly OnPageElementsAnalyzer $onPageElementsAnalyzer,
    ) {
        $this->config = config('seo-intelligence', []);
    }

    public function analyzePost(Post $post): SeoAnalysis
    {
        $content = $post->content ?? '';
        $focusKeyword = $post->focus_keyword;

        // Auto-generate focus keyword from title if not set
        if (empty($focusKeyword)) {
            $focusKeyword = $this->extractKeywordFromTitle($post->title);
            $post->focus_keyword = $focusKeyword;
            $post->saveQuietly();
        }

        // Run all 5 analyzers
        $contentQuality = $this->contentQualityAnalyzer->analyze($content, $focusKeyword);
        $technicalSeo = $this->technicalSeoAnalyzer->analyze(
            $content,
            $post->meta_title,
            $post->meta_description,
            $post->slug,
            $post->title,
        );
        $readability = $this->readabilityAnalyzer->analyze($content);
        $userEngagement = $this->userEngagementAnalyzer->analyze($content, $post->id);
        $onPageElements = $this->onPageElementsAnalyzer->analyze(
            $content,
            $post->featured_image,
            $post->excerpt,
        );

        // Calculate weighted overall score
        $weights = $this->config['weights'] ?? [
            'content_quality' => 0.30,
            'technical_seo' => 0.25,
            'readability' => 0.20,
            'user_engagement' => 0.15,
            'on_page_elements' => 0.10,
        ];

        $overallScore = (int) round(
            ($contentQuality['score'] * $weights['content_quality'])
            + ($technicalSeo['score'] * $weights['technical_seo'])
            + ($readability['score'] * $weights['readability'])
            + ($userEngagement['score'] * $weights['user_engagement'])
            + ($onPageElements['score'] * $weights['on_page_elements'])
        );

        $overallScore = max(0, min(100, $overallScore));
        $grade = $this->calculateGrade($overallScore);

        // Generate feedback from scores
        $feedback = $this->generateFallbackFeedback([
            'content_quality' => $contentQuality,
            'technical_seo' => $technicalSeo,
            'readability' => $readability,
            'user_engagement' => $userEngagement,
            'on_page_elements' => $onPageElements,
        ], $overallScore);

        // Store analysis
        $analysis = SeoAnalysis::create([
            'post_id' => $post->id,
            'overall_score' => $overallScore,
            'grade' => $grade,
            'content_quality_score' => $contentQuality['score'],
            'technical_seo_score' => $technicalSeo['score'],
            'readability_score' => $readability['score'],
            'user_engagement_score' => $userEngagement['score'],
            'on_page_elements_score' => $onPageElements['score'],
            'content_quality_details' => $contentQuality,
            'technical_seo_details' => $technicalSeo,
            'readability_details' => $readability,
            'user_engagement_details' => $userEngagement,
            'on_page_elements_details' => $onPageElements,
            'strengths' => $feedback['strengths'] ?? [],
            'improvements' => $feedback['improvements'] ?? [],
            'critical_issues' => $feedback['critical_issues'] ?? [],
            'keyword_suggestions' => $feedback['keyword_suggestions'] ?? [],
            'content_recommendations' => $feedback['content_recommendations'] ?? [],
            'google_trends_data' => [],
            'word_count_at_analysis' => $contentQuality['word_count'] ?? 0,
            'focus_keyword_at_analysis' => $focusKeyword,
        ]);

        // Cache invalidation
        $this->clearPostCache($post->id);

        return $analysis;
    }

    public function getLatestAnalysis(Post $post): ?SeoAnalysis
    {
        return SeoAnalysis::where('post_id', $post->id)
            ->latest()
            ->first();
    }

    public function canAnalyze(Post $post): bool
    {
        $cooldown = $this->config['rate_limit']['cooldown_seconds'] ?? 1800;
        $lastAnalysis = $this->getLatestAnalysis($post);

        if (!$lastAnalysis) {
            return true;
        }

        // Allow immediate re-analysis after applying recommendations
        if (!empty($lastAnalysis->applied_recommendations)) {
            return true;
        }

        return $lastAnalysis->created_at->addSeconds($cooldown)->isPast();
    }

    public function getCooldownRemaining(Post $post): int
    {
        $cooldown = $this->config['rate_limit']['cooldown_seconds'] ?? 1800;
        $lastAnalysis = $this->getLatestAnalysis($post);

        if (!$lastAnalysis) {
            return 0;
        }

        // No cooldown after applying recommendations
        if (!empty($lastAnalysis->applied_recommendations)) {
            return 0;
        }

        $expiresAt = $lastAnalysis->created_at->addSeconds($cooldown);

        if ($expiresAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInSeconds($expiresAt);
    }

    public function calculateGrade(int $score): string
    {
        $grades = $this->config['grades'] ?? [
            'A+' => 95, 'A' => 90, 'B+' => 85, 'B' => 80,
            'C+' => 75, 'C' => 70, 'D' => 60, 'F' => 0,
        ];

        foreach ($grades as $grade => $threshold) {
            if ($score >= $threshold) {
                return $grade;
            }
        }

        return 'F';
    }

    public function getGradeColor(string $grade): string
    {
        return match (true) {
            in_array($grade, ['A+', 'A']) => 'green',
            in_array($grade, ['B+', 'B']) => 'blue',
            in_array($grade, ['C+', 'C']) => 'amber',
            default => 'red',
        };
    }

    public function applyRecommendations(Post $post, SeoAnalysis $analysis): array
    {
        $keywordSuggestions = $analysis->keyword_suggestions ?? [];

        // Use AI to generate all optimizations in one call
        $aiOptimizations = $this->generateAiOptimizations($post, $analysis);

        $applied = [];

        // 1. Apply AI-optimized meta title
        if (!empty($aiOptimizations['meta_title'])) {
            $newTitle = $aiOptimizations['meta_title'];
            if ($newTitle !== ($post->meta_title ?: $post->title)) {
                $oldLen = mb_strlen($post->meta_title ?: $post->title);
                $newLen = mb_strlen($newTitle);
                $applied[] = [
                    'type' => 'meta_title',
                    'description' => "Meta Title: Updated ({$oldLen} → {$newLen} chars)",
                    'old' => $post->meta_title ?: $post->title,
                    'new' => $newTitle,
                ];
                $post->meta_title = $newTitle;
            }
        }

        // 2. Apply AI-optimized meta description
        if (!empty($aiOptimizations['meta_description'])) {
            $newDesc = $aiOptimizations['meta_description'];
            if ($newDesc !== $post->meta_description) {
                $applied[] = [
                    'type' => 'meta_description',
                    'description' => 'Meta Description: Optimized (' . mb_strlen($newDesc) . ' chars)',
                    'old' => $post->meta_description ?: '',
                    'new' => $newDesc,
                ];
                $post->meta_description = $newDesc;
            }
        }

        // 3. Apply AI-optimized content
        if (!empty($aiOptimizations['optimized_content'])) {
            $newContent = $aiOptimizations['optimized_content'];

            // Handle truncated content re-attachment
            $truncationMarker = '<!--TRUNCATED_CONTENT_CONTINUES-->';
            if (str_contains($newContent, $truncationMarker)) {
                $optimizedPart = str_replace($truncationMarker, '', $newContent);
                $originalTruncPoint = min(20000, mb_strlen($post->content));
                $remainingOriginal = mb_substr($post->content, $originalTruncPoint);
                $newContent = !empty($remainingOriginal) ? $optimizedPart . $remainingOriginal : $optimizedPart;
            }

            if ($newContent !== $post->content) {
                // Safety: don't accept content that lost >20% of original word count
                $originalWordCount = str_word_count(strip_tags($post->content));
                $newWordCount = str_word_count(strip_tags($newContent));
                if ($originalWordCount > 0 && $newWordCount < ($originalWordCount * 0.8)) {
                    Log::warning('AI optimization returned significantly shorter content, skipping', [
                        'post_id' => $post->id,
                        'original_words' => $originalWordCount,
                        'new_words' => $newWordCount,
                    ]);
                } else {
                    $changes = $aiOptimizations['content_changes'] ?? ['Incorporated keywords and improved structure'];
                    $applied[] = [
                        'type' => 'content_optimization',
                        'description' => 'Content: ' . implode(', ', array_slice($changes, 0, 3)),
                        'old' => '',
                        'new' => 'Content optimized with AI',
                    ];
                    $post->content = $newContent;
                }
            }
        }

        // 4. Add internal links if needed
        $currentInternalLinks = $this->countInternalLinksInContent($post->content);
        if ($currentInternalLinks < 3) {
            $suggestedLinks = $this->suggestInternalLinks($post);
            if (!empty($suggestedLinks)) {
                $linkHtml = $this->buildInternalLinksSection($suggestedLinks);
                $post->content = $post->content . $linkHtml;
                $applied[] = [
                    'type' => 'internal_links',
                    'description' => 'Added ' . count($suggestedLinks) . ' internal links',
                    'old' => '',
                    'new' => $linkHtml,
                ];
            }
        }

        // 5. Add keyword suggestions as tags
        if (!empty($keywordSuggestions)) {
            $existingTags = $post->tags->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
            $addedTags = [];
            $addedNames = [];

            foreach ($keywordSuggestions as $keyword) {
                if (count($addedTags) >= 4) break;
                if (!in_array(strtolower($keyword), $existingTags)) {
                    $tag = \App\Models\Tag::firstOrCreate(
                        ['slug' => \Illuminate\Support\Str::slug($keyword)],
                        ['name' => $keyword]
                    );
                    $addedTags[] = $tag->id;
                    $addedNames[] = $keyword;
                }
            }

            if (!empty($addedTags)) {
                $post->tags()->syncWithoutDetaching($addedTags);
                $applied[] = [
                    'type' => 'tags',
                    'description' => 'Added ' . count($addedTags) . ' keyword suggestions as tags',
                    'old' => '',
                    'new' => implode(', ', $addedNames),
                ];
            }
        }

        // Save
        if (!empty($applied)) {
            $post->save();
            $analysis->update(['applied_recommendations' => $applied]);
        }

        return $applied;
    }

    private function generateAiOptimizations(Post $post, SeoAnalysis $analysis): array
    {
        $technicalDetails = $analysis->technical_seo_details ?? [];
        $contentDetails = $analysis->content_quality_details ?? [];
        $readabilityDetails = $analysis->readability_details ?? [];
        $engagementDetails = $analysis->user_engagement_details ?? [];
        $onPageDetails = $analysis->on_page_elements_details ?? [];
        $keywordSuggestions = $analysis->keyword_suggestions ?? [];
        $improvements = $analysis->improvements ?? [];
        $contentRecommendations = $analysis->content_recommendations ?? [];

        $focusKeyword = $post->focus_keyword ?? '';
        $currentMetaTitle = $post->meta_title ?: $post->title;
        $currentMetaDesc = $post->meta_description ?: '';
        $keywordsStr = !empty($keywordSuggestions) ? implode(', ', $keywordSuggestions) : 'none provided';
        $improvementsStr = !empty($improvements) ? implode("\n- ", $improvements) : 'none';
        $headerIssues = $technicalDetails['header_issues'] ?? [];
        $headerIssuesStr = !empty($headerIssues) ? implode("\n- ", $headerIssues) : 'none';

        // Build readability context
        $readabilityIssues = [];
        $fleschScore = $readabilityDetails['flesch_score'] ?? 0;
        $avgSentenceLength = $readabilityDetails['avg_sentence_length'] ?? 0;
        $passiveVoicePct = $readabilityDetails['passive_voice_percentage'] ?? 0;
        $transitionWordPct = $readabilityDetails['transition_word_percentage'] ?? 0;
        $complexWordPct = $readabilityDetails['complex_word_percentage'] ?? 0;
        $avgParagraphLength = $readabilityDetails['avg_paragraph_length'] ?? 0;

        if ($fleschScore < 60) $readabilityIssues[] = "Flesch Reading Ease is {$fleschScore} (target: 60-80). Simplify language.";
        if ($avgSentenceLength > 20) $readabilityIssues[] = "Avg sentence length is {$avgSentenceLength} words (target: 15-20). Break long sentences.";
        if ($passiveVoicePct > 15) $readabilityIssues[] = "Passive voice at {$passiveVoicePct}% (target: <15%). Use active voice.";
        if ($transitionWordPct < 25) $readabilityIssues[] = "Transition words at {$transitionWordPct}% (target: 25%+). Add: however, furthermore, additionally, therefore.";
        if ($complexWordPct > 15) $readabilityIssues[] = "Complex words at {$complexWordPct}% (target: <15%). Use simpler alternatives.";
        if ($avgParagraphLength > 5) $readabilityIssues[] = "Avg paragraph length is {$avgParagraphLength} sentences (target: 3-5). Break up paragraphs.";
        $readabilityStr = !empty($readabilityIssues) ? implode("\n- ", $readabilityIssues) : 'none';

        // Keyword density context
        $keywordDensity = $contentDetails['keyword_density'] ?? 0;
        $keywordStuffingStr = 'none';
        if (($contentDetails['keyword_stuffing_detected'] ?? false)) {
            $keywordStuffingStr = "CRITICAL: Keyword stuffing detected ({$keywordDensity}%). Reduce to 1.0-2.0%.";
        } elseif ($keywordDensity > 2.0) {
            $keywordStuffingStr = "WARNING: Density at {$keywordDensity}%. Reduce to 1.0-2.0%.";
        }

        // Engagement context
        $engagementIssues = [];
        if (($engagementDetails['external_links_count'] ?? 0) < 2) $engagementIssues[] = 'Add 2-3 external links to authoritative sources.';
        if (!($onPageDetails['has_lists'] ?? false)) $engagementIssues[] = 'Add bullet/numbered lists for scannability.';
        if (($onPageDetails['header_count'] ?? 0) < 3) $engagementIssues[] = 'Add more H2/H3 subheadings.';
        $engagementStr = !empty($engagementIssues) ? implode("\n- ", $engagementIssues) : 'none';
        $contentRecsStr = !empty($contentRecommendations) ? implode("\n- ", $contentRecommendations) : 'none';
        $imageCount = $onPageDetails['image_count'] ?? 0;

        // Truncate long content for AI
        $contentForPrompt = $post->content;
        $isLongContent = mb_strlen($contentForPrompt) > 20000;
        if ($isLongContent) {
            $contentForPrompt = mb_substr($contentForPrompt, 0, 20000) . "\n\n[... CONTENT TRUNCATED ...]";
        }
        $truncationWarning = $isLongContent
            ? "\n\nCRITICAL: Content was truncated. Include ALL shown content. Append <!--TRUNCATED_CONTENT_CONTINUES--> at the end."
            : '';

        $systemPrompt = <<<PROMPT
You are an expert SEO content optimizer for Topping Africa, a leading African news and magazine website. Optimize blog posts for SEO AND readability while preserving the author's voice and ALL existing content.

CRITICAL SCORING RULES:
1. META TITLE: 50-60 characters (scores 100). Include focus keyword.
2. META DESCRIPTION: 150-160 characters (scores 100). Compelling for clicks.
3. KEYWORD DENSITY: 1.0-2.0% (optimal). Below 1% loses points. Above 3% = stuffing.
4. KEYWORD PLACEMENT: Must appear in first paragraph, at least one heading, distributed across sections.
5. READABILITY: Flesch Reading Ease 60-80. Sentences 15-20 words. Paragraphs 3-5 sentences.
6. TRANSITION WORDS: 25%+ of sentences start with transitions (however, furthermore, additionally, therefore, meanwhile, consequently).
7. PASSIVE VOICE: Below 15%.
8. EXTERNAL LINKS: 2-3 links to authoritative sources with target="_blank" rel="noopener noreferrer".
9. STRUCTURE: 4+ subheadings (H2/H3), at least 1 list, bold and italic text.
10. CALL TO ACTION: Include 2+ CTA phrases (explore, discover, read more, share).

Content rules:
- NEVER remove existing content - only enhance and add
- If the content is under 1500 words, EXPAND it significantly with relevant details, examples, context, and analysis to reach at least 1500 words
- Naturally incorporate the focus keyword throughout - aim for 1.0-2.0% density
- The focus keyword MUST appear in: the first paragraph, at least one H2/H3 heading, and be distributed across 30-70% of content sections
- If keyword density is already 1.0-2.0%, maintain it
- If above 2%, replace some instances with synonyms/pronouns
- Keep all existing HTML structure and images
- Fix header hierarchy issues
- Output FULL optimized HTML content - never summarize or truncate

Readability rules (CRITICAL - these directly affect the score):
- TARGET Flesch Reading Ease of 60-80. This means using SHORT, SIMPLE words and sentences.
- Replace ALL complex/academic words (3+ syllables) with simpler alternatives. Examples: "approximately" → "about", "subsequently" → "then", "implementation" → "use", "infrastructure" → "systems", "significant" → "major", "comprehensive" → "full", "demonstrate" → "show", "facilitate" → "help", "utilize" → "use", "establish" → "set up", "fundamental" → "key", "nevertheless" → "still"
- Keep complex word percentage BELOW 15% of total words
- Break ALL sentences longer than 20 words into 2 shorter sentences
- Keep average sentence length between 15-20 words
- Add transition words at the START of 25%+ of sentences
- Convert passive voice to active voice everywhere
- Break paragraphs into 3-5 sentences max
- Write at a 7th-8th grade reading level - imagine writing for a general news audience

Do NOT add a "Related Articles" section.
Always respond in valid JSON.{$truncationWarning}
PROMPT;

        $wordCount = str_word_count(strip_tags($post->content));
        $wordCountWarning = $wordCount < 1500
            ? "CRITICAL: Content is only {$wordCount} words. Target is 1500-2500. You MUST expand the content significantly with additional paragraphs, details, analysis, context, examples, and expert insights."
            : "Current word count: {$wordCount} (good)";

        $userPrompt = <<<PROMPT
Optimize this blog post for SEO and readability.

FOCUS KEYWORD: {$focusKeyword}
CURRENT META TITLE ({$this->mbStrlenSafe($currentMetaTitle)} chars): {$currentMetaTitle}
CURRENT META DESCRIPTION ({$this->mbStrlenSafe($currentMetaDesc)} chars): {$currentMetaDesc}

WORD COUNT: {$wordCountWarning}

SEMANTIC KEYWORDS: {$keywordsStr}

ISSUES TO FIX:
- {$improvementsStr}

HEADER ISSUES:
- {$headerIssuesStr}

READABILITY ISSUES:
- {$readabilityStr}

KEYWORD DENSITY ISSUES:
- {$keywordStuffingStr}

ENGAGEMENT ISSUES:
- {$engagementStr}

CONTENT RECOMMENDATIONS:
- {$contentRecsStr}

CONTENT IMAGES: {$imageCount}

CURRENT CONTENT:
{$contentForPrompt}

Respond in this exact JSON format:
{
    "meta_title": "Optimized meta title (50-60 chars)",
    "meta_description": "Optimized meta description (150-160 chars)",
    "optimized_content": "Full optimized HTML content",
    "content_changes": ["Change 1", "Change 2", "Change 3"]
}
PROMPT;

        try {
            $response = $this->callAnthropicApi($systemPrompt, $userPrompt);
            if ($response) {
                $parsed = $this->parseJsonFromResponse($response);
                if ($parsed && (isset($parsed['meta_title']) || isset($parsed['optimized_content']))) {
                    return $parsed;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI optimization failed, falling back to basic', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to basic optimizations
        return $this->generateBasicOptimizations($post, $analysis);
    }

    private function callAnthropicApi(string $systemPrompt, string $userPrompt): ?string
    {
        $apiKey = config('blog.ai.providers.anthropic.api_key') ?: env('ANTHROPIC_API_KEY');
        if (!$apiKey) {
            // Try OpenAI as fallback
            return $this->callOpenAiApi($systemPrompt, $userPrompt);
        }

        $model = config('blog.ai.providers.anthropic.model', 'claude-sonnet-4-6');

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 8000,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }

        Log::error('Anthropic API error', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    private function callOpenAiApi(string $systemPrompt, string $userPrompt): ?string
    {
        $apiKey = config('blog.ai.providers.openai.api_key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) return null;

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => config('blog.ai.providers.openai.model', 'gpt-4o'),
            'max_tokens' => 8000,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    private function parseJsonFromResponse(string $content): ?array
    {
        // Try direct JSON parse
        $decoded = json_decode($content, true);
        if ($decoded) return $decoded;

        // Try extracting from markdown code block
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded) return $decoded;
        }

        // Try finding JSON object in text
        if (preg_match('/\{[\s\S]*"meta_title"[\s\S]*\}/m', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded) return $decoded;
        }

        return null;
    }

    private function generateBasicOptimizations(Post $post, SeoAnalysis $analysis): array
    {
        $result = [];
        $technicalDetails = $analysis->technical_seo_details ?? [];

        if (($technicalDetails['meta_title_status'] ?? '') !== 'optimal') {
            $optimized = $this->basicOptimizeMetaTitle($post->title, $post->focus_keyword);
            if ($optimized) $result['meta_title'] = $optimized;
        }

        if (($technicalDetails['meta_description_status'] ?? '') !== 'optimal') {
            $optimized = $this->basicOptimizeMetaDescription($post->title, $post->excerpt, $post->focus_keyword);
            if ($optimized) $result['meta_description'] = $optimized;
        }

        return $result;
    }

    private function mbStrlenSafe(?string $str): int
    {
        return $str ? mb_strlen($str) : 0;
    }

    private function extractKeywordFromTitle(string $title): string
    {
        // Remove common stop words and extract the most meaningful phrase
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'shall', 'can', 'need', 'dare', 'ought', 'used', 'to', 'of',
            'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as', 'into', 'through',
            'during', 'before', 'after', 'above', 'below', 'between', 'out', 'off',
            'over', 'under', 'again', 'further', 'then', 'once', 'and', 'but', 'or',
            'nor', 'not', 'so', 'yet', 'both', 'either', 'neither', 'each', 'every',
            'all', 'any', 'few', 'more', 'most', 'other', 'some', 'such', 'no',
            'only', 'own', 'same', 'than', 'too', 'very', 'just', 'because', 'how',
            'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am',
            'about', 'announces', 'new', 'its', 'his', 'her', 'their', 'says'];

        $words = preg_split('/\s+/', strtolower(trim($title)));
        $meaningful = array_filter($words, function ($word) use ($stopWords) {
            $clean = preg_replace('/[^a-z0-9]/', '', $word);
            return strlen($clean) > 2 && !in_array($clean, $stopWords);
        });

        // Take the first 3-4 meaningful words as the keyword phrase
        $keyword = implode(' ', array_slice(array_values($meaningful), 0, 4));

        return $keyword ?: strtolower(implode(' ', array_slice($words, 0, 3)));
    }

    public function getApplyPreview(Post $post, SeoAnalysis $analysis): array
    {
        $preview = [];
        $technicalDetails = $analysis->technical_seo_details ?? [];
        $engagementDetails = $analysis->user_engagement_details ?? [];

        // Meta title
        if (($technicalDetails['meta_title_status'] ?? '') !== 'optimal') {
            $currentTitle = $post->meta_title ?: $post->title;
            $preview[] = [
                'type' => 'meta_title',
                'icon' => 'check',
                'description' => 'Meta Title: Will optimize for SEO (' . mb_strlen($currentTitle) . ' chars -> 50-60 chars target)',
                'old' => $currentTitle,
                'new' => 'Will be optimized',
            ];
        }

        // Meta description
        if (($technicalDetails['meta_description_status'] ?? '') !== 'optimal') {
            $preview[] = [
                'type' => 'meta_description',
                'icon' => 'check',
                'description' => 'Meta Description: Will create keyword-rich description (150-160 chars target)',
                'old' => $post->meta_description ?: '(empty)',
                'new' => 'Will be optimized',
            ];
        }

        // Content optimization
        $contentIssues = [];
        $contentDetails = $analysis->content_quality_details ?? [];
        $readabilityDetails = $analysis->readability_details ?? [];

        $keywordDensity = $contentDetails['keyword_density'] ?? 0;
        if ($keywordDensity < 1.0) {
            $contentIssues[] = 'Add focus keyword naturally';
        }
        if ($keywordDensity > 2.0) {
            $contentIssues[] = 'Reduce keyword density from ' . number_format($keywordDensity, 1) . '% to 1-2% (Google spam policy)';
        }
        if (!($contentDetails['keyword_in_first_paragraph'] ?? false)) {
            $contentIssues[] = 'Add keyword to first paragraph';
        }
        if (!($contentDetails['keyword_in_headings'] ?? false)) {
            $contentIssues[] = 'Add keyword to headings';
        }

        $keywordSuggestions = $analysis->keyword_suggestions ?? [];
        if (!empty($keywordSuggestions)) {
            $contentIssues[] = 'Weave in ' . count($keywordSuggestions) . ' semantic keywords';
        }

        $headerIssues = $technicalDetails['header_issues'] ?? [];
        if (!empty($headerIssues)) {
            $contentIssues[] = 'Fix header hierarchy';
        }

        // Readability issues
        if (($readabilityDetails['avg_sentence_length'] ?? 0) > 20) {
            $contentIssues[] = 'Shorten long sentences';
        }
        if (($readabilityDetails['transition_word_percentage'] ?? 0) < 25) {
            $contentIssues[] = 'Add transition words';
        }
        if (($readabilityDetails['passive_voice_percentage'] ?? 0) > 15) {
            $contentIssues[] = 'Reduce passive voice';
        }
        if (($readabilityDetails['complex_word_percentage'] ?? 0) > 15) {
            $contentIssues[] = 'Simplify complex words';
        }

        // Engagement & structure issues
        if (($engagementDetails['external_links_count'] ?? 0) < 2) {
            $contentIssues[] = 'Add external links to authoritative sources';
        }
        $onPageDetails = $analysis->on_page_elements_details ?? [];
        if (($onPageDetails['header_count'] ?? 0) < 3) {
            $contentIssues[] = 'Add more subheadings';
        }
        if (!($onPageDetails['has_lists'] ?? false)) {
            $contentIssues[] = 'Add bullet points/lists';
        }

        if (!empty($contentIssues)) {
            $preview[] = [
                'type' => 'content_optimization',
                'icon' => 'magic',
                'description' => 'Content: Will ' . implode(', ', array_slice($contentIssues, 0, 5)),
                'old' => '',
                'new' => 'Content will be improved while preserving your voice and meaning',
            ];
        }

        // Internal links
        if (($engagementDetails['internal_links_count'] ?? 0) < 3) {
            $suggestedLinks = $this->suggestInternalLinks($post);
            if (!empty($suggestedLinks)) {
                $preview[] = [
                    'type' => 'internal_links',
                    'icon' => 'link',
                    'description' => 'Add ' . count($suggestedLinks) . ' internal links to related posts',
                    'old' => '',
                    'new' => implode(', ', array_column($suggestedLinks, 'title')),
                ];
            }
        }

        // Keyword suggestions as tags
        if (!empty($keywordSuggestions)) {
            $existingTags = $post->tags->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
            $newKeywords = array_filter($keywordSuggestions, fn ($k) => !in_array(strtolower($k), $existingTags));
            if (!empty($newKeywords)) {
                $preview[] = [
                    'type' => 'tags',
                    'icon' => 'tags',
                    'description' => 'Add ' . count($newKeywords) . ' keyword suggestions as post tags',
                    'old' => '',
                    'new' => implode(', ', $newKeywords),
                ];
            }
        }

        return $preview;
    }

    private function generateFallbackFeedback(array $scores, int $overallScore): array
    {
        $strengths = [];
        $improvements = [];
        $criticalIssues = [];

        foreach ($scores as $category => $data) {
            $score = $data['score'] ?? 0;
            $label = str_replace('_', ' ', ucfirst($category));

            if ($score >= 80) {
                $strengths[] = "{$label} is well optimized ({$score}/100)";
            } elseif ($score < 50) {
                $criticalIssues[] = "{$label} needs significant improvement ({$score}/100)";
            } else {
                $improvements[] = "Improve {$label} (currently {$score}/100)";
            }
        }

        return [
            'strengths' => array_slice($strengths, 0, 3),
            'improvements' => array_slice($improvements, 0, 5),
            'critical_issues' => $criticalIssues,
            'keyword_suggestions' => [],
            'content_recommendations' => ['Add more internal links', 'Improve header hierarchy', 'Add more media content'],
        ];
    }

    private function basicOptimizeMetaTitle(string $title, ?string $keyword): ?string
    {
        $targets = $this->config['targets']['meta_title_length'] ?? ['min' => 50, 'max' => 60];
        $currentLength = mb_strlen($title);

        if ($currentLength >= $targets['min'] && $currentLength <= $targets['max']) {
            return null; // Already optimal
        }

        // If too long, truncate intelligently
        if ($currentLength > $targets['max']) {
            $truncated = mb_substr($title, 0, $targets['max'] - 3) . '...';
            return $truncated;
        }

        // If too short and we have a keyword, append it
        if ($currentLength < $targets['min'] && !empty($keyword)) {
            $withKeyword = $title . ' | ' . ucfirst($keyword);
            if (mb_strlen($withKeyword) <= $targets['max']) {
                return $withKeyword;
            }
        }

        return null;
    }

    private function basicOptimizeMetaDescription(string $title, ?string $excerpt, ?string $keyword): ?string
    {
        $targets = $this->config['targets']['meta_description_length'] ?? ['min' => 150, 'max' => 160];

        if (!empty($excerpt) && mb_strlen($excerpt) >= $targets['min'] && mb_strlen($excerpt) <= $targets['max']) {
            return null; // Already optimal
        }

        // Build from excerpt or title
        $base = $excerpt ?: $title;
        $description = $base;

        // Ensure keyword is included
        if (!empty($keyword) && stripos($description, $keyword) === false) {
            $description .= '. Learn about ' . $keyword . '.';
        }

        // Adjust length
        if (mb_strlen($description) > $targets['max']) {
            $description = mb_substr($description, 0, $targets['max'] - 3) . '...';
        }

        if (mb_strlen($description) < $targets['min']) {
            $description .= ' Read more to discover tips and best practices.';
        }

        // Final trim
        if (mb_strlen($description) > $targets['max']) {
            $description = mb_substr($description, 0, $targets['max'] - 3) . '...';
        }

        return $description;
    }

    private function countInternalLinksInContent(string $content): int
    {
        $appUrl = config('app.url', '');
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?? '';

        preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*>/si', $content, $matches);

        $count = 0;
        foreach ($matches[1] ?? [] as $url) {
            $urlHost = parse_url($url, PHP_URL_HOST);
            if ($urlHost === $appHost || ($urlHost === null && str_starts_with($url, '/'))) {
                $count++;
            }
        }

        return $count;
    }

    private function suggestInternalLinks(Post $post): array
    {
        $relatedPosts = Post::where('id', '!=', $post->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit(10)
            ->get();

        $suggestions = [];
        $focusKeyword = strtolower($post->focus_keyword ?? '');
        $titleWords = explode(' ', strtolower($post->title));

        foreach ($relatedPosts as $related) {
            $relatedTitle = strtolower($related->title);
            $relevance = 0;

            // Check keyword match
            if (!empty($focusKeyword) && stripos($relatedTitle, $focusKeyword) !== false) {
                $relevance += 3;
            }

            // Check title word overlap
            foreach ($titleWords as $word) {
                if (strlen($word) > 3 && stripos($relatedTitle, $word) !== false) {
                    $relevance++;
                }
            }

            // Category overlap
            $categoryOverlap = $post->categories->pluck('id')
                ->intersect($related->categories->pluck('id'))->count();
            $relevance += $categoryOverlap * 2;

            if ($relevance >= 2) {
                $suggestions[] = [
                    'title' => $related->title,
                    'url' => route('blog.show', $related->slug),
                    'relevance' => $relevance,
                ];
            }
        }

        usort($suggestions, fn($a, $b) => $b['relevance'] <=> $a['relevance']);

        return array_slice($suggestions, 0, 3);
    }

    private function buildInternalLinksSection(array $links): string
    {
        if (empty($links)) {
            return '';
        }

        $html = "\n\n<h2>Related Articles</h2>\n<ul>\n";
        foreach ($links as $link) {
            $title = htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8');
            $html .= "  <li><a href=\"{$url}\">{$title}</a></li>\n";
        }
        $html .= "</ul>\n";

        return $html;
    }

    private function clearPostCache(int $postId): void
    {
        $prefix = $this->config['cache']['prefix'] ?? 'seo_intelligence_';
        Cache::forget($prefix . 'post_' . $postId);
    }
}
