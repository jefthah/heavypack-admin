<x-filament-widgets::widget>
    <section class="hp-hero">
        <div>
            <p class="hp-hero__eyebrow">
                <x-filament::icon icon="heroicon-m-calendar-days" />
                {{ $date }}
            </p>

            <h2 class="hp-hero__title">
                {{ $greeting }}@if (filled($name)), <span class="hp-hero__title-name">{{ $name }}</span>@endif
            </h2>

            <p class="hp-hero__text">
                Kelola katalog mesin, sparepart, artikel, dan media website HeavyPack dari satu tempat.
            </p>

            <p class="hp-hero__status">
                <span @class(['hp-dot', 'hp-dot--testing' => ! $isSharedCatalog]) aria-hidden="true"></span>
                <span>
                    <strong>{{ $environmentLabel }}</strong>
                    · {{ $isSharedCatalog ? 'Perubahan konten langsung digunakan website lokal.' : 'Perubahan tersimpan di katalog pengujian.' }}
                </span>
            </p>
        </div>

        <nav class="hp-hero__actions" aria-label="Aksi cepat">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" @class(['hp-action', 'hp-action--primary' => $action['primary']])>
                    <x-filament::icon :icon="$action['icon']" />
                    {{ $action['label'] }}
                </a>
            @endforeach
        </nav>
    </section>
</x-filament-widgets::widget>
