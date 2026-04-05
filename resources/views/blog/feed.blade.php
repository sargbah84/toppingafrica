<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ \App\Models\Setting::get('site_name', config('app.name')) }}</title>
        <link>{{ config('app.url') }}</link>
        <description>{{ \App\Models\Setting::get('site_description', config('app.name')) }}</description>
        <language>en</language>
        <lastBuildDate>{{ $posts->first()?->published_at?->toRssString() ?? now()->toRssString() }}</lastBuildDate>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml"/>
        <copyright>{{ date('Y') }} {{ \App\Models\Setting::get('site_name', config('app.name')) }}. All rights reserved.</copyright>

        @foreach($posts as $post)
        <item>
            <title><![CDATA[{{ $post->title }}]]></title>
            <link>{{ route('blog.show', $post->slug) }}</link>
            <description><![CDATA[{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 300) }}]]></description>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
            <guid isPermaLink="true">{{ route('blog.show', $post->slug) }}</guid>
            @if($post->author)
            <author>{{ $post->author->email }} ({{ $post->author->name }})</author>
            @endif
            @foreach($post->categories as $category)
            <category><![CDATA[{{ $category->name }}]]></category>
            @endforeach
            @if($post->featured_image_url)
            <enclosure url="{{ $post->featured_image_url }}" type="image/jpeg" length="0"/>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
