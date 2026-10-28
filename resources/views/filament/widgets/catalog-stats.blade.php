<x-filament-widgets::widget class="hp-stats-ctn">
    <div class="hp-stats">
        @foreach ($stats as $stat)
            @php
                $tag = $stat['url'] ? 'a' : 'div';
            @endphp

            <{!! $tag !!}
                @if ($stat['url']) href="{{ $stat['url'] }}" @endif
                class="hp-stat"
            >
                <span class="hp-stat__icon">
                    <x-filament::icon :icon="$stat['icon']" />
                </span>

                <span class="hp-stat__label">{{ $stat['label'] }}</span>
                <span class="hp-stat__value">{{ $stat['value'] }}</span>

                <span class="hp-stat__meta">
                    <span>{{ $stat['description'] }}</span>

                    @if ($stat['url'])
                        <x-filament::icon icon="heroicon-m-arrow-right" class="hp-stat__arrow" />
                    @endif
                </span>
            </{!! $tag !!}>
        @endforeach
    </div>
</x-filament-widgets::widget>
