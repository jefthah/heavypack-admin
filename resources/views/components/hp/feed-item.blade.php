@props([
    'url' => null,
    'top' => false,
])

@php
    $tag = filled($url) ? 'a' : 'div';
@endphp

<li>
    <{!! $tag !!}
        @if (filled($url)) href="{{ $url }}" @endif
        {{ $attributes->class(['hp-feed__item', 'hp-feed__item--top' => $top]) }}
    >
        {{ $slot }}

        @if (filled($url))
            <x-filament::icon icon="heroicon-m-chevron-right" class="hp-feed__chevron" />
        @endif
    </{!! $tag !!}>
</li>
