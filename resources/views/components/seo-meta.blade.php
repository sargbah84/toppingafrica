@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'canonical' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'ogImageAlt' => null,
    'twitterCard' => 'summary_large_image',
    'author' => null,
    'publishedTime' => null,
    'modifiedTime' => null,
    'section' => null,
    'tags' => null,
    'noindex' => false,
])

@php
    $siteName = config('app.name', 'Topping Africa');
    $siteUrl = config('app.url');

    $metaTitle = $title ?? $siteName;
    $metaDescription = $description ?? 'African News, Entertainment, Business, Technology, Sports & Culture - Topping Africa';
    $metaCanonical = $canonical ?? url()->current();

    $defaultOgImage = file_exists(public_path('images/og-default.png'))
        ? asset('images/og-default.png')
        : (file_exists(public_path('img/og-default.png')) ? asset('img/og-default.png') : asset('images/logo.png'));
    $metaImage = $ogImage ?? $defaultOgImage;
    $metaImageAlt = $ogImageAlt ?? $metaTitle;
@endphp

{{-- Primary Meta Tags --}}
<meta name="description" content="{{ Str::limit($metaDescription, 160) }}">
@if($keywords)
<meta name="keywords" content="{{ is_array($keywords) ? implode(', ', $keywords) : $keywords }}">
@endif
@if($author)
<meta name="author" content="{{ $author }}">
@endif
@if($noindex)
<meta name="robots" content="noindex, nofollow">
@else
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@endif

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $metaCanonical }}">

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $metaCanonical }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ Str::limit($metaDescription, 200) }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:alt" content="{{ $metaImageAlt }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="en_US">

{{-- Article specific Open Graph --}}
@if($ogType === 'article')
    @if($publishedTime)
    <meta property="article:published_time" content="{{ $publishedTime }}">
    @endif
    @if($modifiedTime)
    <meta property="article:modified_time" content="{{ $modifiedTime }}">
    @endif
    @if($author)
    <meta property="article:author" content="{{ $author }}">
    @endif
    @if($section)
    <meta property="article:section" content="{{ $section }}">
    @endif
    @if($tags)
        @foreach(is_array($tags) ? $tags : [$tags] as $tag)
        <meta property="article:tag" content="{{ $tag }}">
        @endforeach
    @endif
@endif

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:url" content="{{ $metaCanonical }}">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ Str::limit($metaDescription, 200) }}">
<meta name="twitter:image" content="{{ $metaImage }}">
<meta name="twitter:image:alt" content="{{ $metaImageAlt }}">
@if(config('schema.organization.social_profiles.twitter'))
<meta name="twitter:site" content="{{ '@' . ltrim(config('schema.organization.social_profiles.twitter'), '@') }}">
@endif
