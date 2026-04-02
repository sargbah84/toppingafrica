@props([
    'pageType' => 'website',
    'context' => [],
])

@php
    $schemaService = app(\App\Services\Schema\SchemaService::class);
    $schemas = [];

    switch ($pageType) {
        case 'home':
            $schemas[] = $schemaService->organization();
            $schemas[] = $schemaService->website();
            break;

        case 'article':
            if (isset($context['post'])) {
                $schemas[] = $schemaService->article($context['post']);
            }
            if (isset($context['breadcrumbs'])) {
                $schemas[] = $schemaService->breadcrumbList($context['breadcrumbs']);
            }
            break;

        case 'news-article':
            if (isset($context['post'])) {
                $schemas[] = $schemaService->newsArticle($context['post']);
            }
            if (isset($context['breadcrumbs'])) {
                $schemas[] = $schemaService->breadcrumbList($context['breadcrumbs']);
            }
            break;

        case 'category':
        case 'tag':
            if (isset($context['breadcrumbs'])) {
                $schemas[] = $schemaService->breadcrumbList($context['breadcrumbs']);
            }
            $schemas[] = $schemaService->organization();
            break;

        default:
            $schemas[] = $schemaService->organization();
            $schemas[] = $schemaService->website();
            break;
    }
@endphp

@foreach($schemas as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
@endforeach
