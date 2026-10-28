@php
    $isSharedCatalog = config('heavypack.content_mode') === 'shared';
@endphp

<div x-show="$store.sidebar.isOpen" class="hp-sidebar-footer">
    <div class="hp-env-card">
        <span @class(['hp-dot', 'hp-dot--testing' => ! $isSharedCatalog]) aria-hidden="true"></span>

        <div>
            <p class="hp-env-card__title">{{ config('heavypack.environment_label') }}</p>
            <p class="hp-env-card__text">
                {{ $isSharedCatalog ? 'Perubahan konten langsung digunakan website lokal.' : 'Perubahan tersimpan di katalog pengujian.' }}
            </p>
        </div>
    </div>
</div>
