@props([
    'title',
    'description' => null,
    'icon',
    'total' => null,
    'totalLabel' => null,
    'url' => null,
    'empty' => 'Belum ada data',
    'isEmpty' => false,
])

<section {{ $attributes->class(['hp-feed']) }}>
    <header class="hp-feed__header">
        <span class="hp-feed__icon" aria-hidden="true">
            <x-filament::icon :icon="$icon" />
        </span>

        <div class="hp-feed__heading">
            <h2 class="hp-feed__title">{{ $title }}</h2>

            @if (filled($description))
                <p class="hp-feed__description">{{ $description }}</p>
            @endif
        </div>

        @if (filled($total))
            <span class="hp-feed__total">{{ $total }} {{ $totalLabel }}</span>
        @endif
    </header>

    @if ($isEmpty)
        <p class="hp-feed__empty">{{ $empty }}</p>
    @else
        <ul class="hp-feed__list" role="list">
            {{ $slot }}
        </ul>
    @endif

    @if (filled($url))
        <footer class="hp-feed__footer">
            <a href="{{ $url }}" class="hp-feed__more">
                Lihat semua
                <x-filament::icon icon="heroicon-m-arrow-right" />
            </a>
        </footer>
    @endif
</section>
