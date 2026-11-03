@php
    $rows = [
        ['label' => 'Produk mesin', 'icon' => 'heroicon-o-cube', 'count' => $products, 'url' => $productsUrl],
        ['label' => 'Sparepart', 'icon' => 'heroicon-o-wrench-screwdriver', 'count' => $spareparts, 'url' => $sparepartsUrl],
    ];
@endphp

<div class="hp-usage">
    <ul class="hp-usage__list">
        @foreach ($rows as $row)
            <li>
                <span class="hp-usage__label">
                    <x-filament::icon :icon="$row['icon']" />
                    {{ $row['label'] }}
                </span>

                @if ($row['count'] > 0)
                    <a href="{{ $row['url'] }}" class="hp-usage__count">
                        {{ number_format($row['count'], 0, ',', '.') }}
                        <x-filament::icon icon="heroicon-m-arrow-right" />
                    </a>
                @else
                    <span class="hp-usage__count hp-usage__count--empty">Belum ada</span>
                @endif
            </li>
        @endforeach
    </ul>

    <p class="hp-usage__note">
        {{ $products + $spareparts > 0
            ? 'Kategori yang masih dipakai produk atau sparepart tidak dapat dihapus.'
            : 'Kategori ini belum dipakai produk atau sparepart mana pun.' }}
    </p>
</div>
