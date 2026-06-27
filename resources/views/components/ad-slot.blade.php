@props(['position'])

@php
    $ads = cache()->remember("ads.{$position}", 3600, function () use ($position) {
        return \App\Models\Ad::active()
            ->position($position)
            ->orderBy('order')
            ->get();
    });
@endphp

@foreach($ads as $ad)
    <div class="ad-slot ad-slot-{{ $position }} my-4">
        @if($ad->code)
            {!! nofollow_ad_links($ad->code) !!}
        @elseif($ad->image)
            @if($ad->url)
                <a href="{{ $ad->url }}" target="_blank" rel="noopener nofollow sponsored">
                    <img src="{{ $ad->image }}" alt="{{ $ad->name }}" class="w-full rounded-sm" loading="lazy">
                </a>
            @else
                <img src="{{ $ad->image }}" alt="{{ $ad->name }}" class="w-full rounded-sm" loading="lazy">
            @endif
        @endif
    </div>
@endforeach
