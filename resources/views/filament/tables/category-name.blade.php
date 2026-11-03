<span class="hp-category-title">
    <span class="hp-category-title__mark" aria-hidden="true">{{ $initials }}</span>

    <span class="hp-category-title__text">
        <span class="hp-category-title__name">{{ $name }}</span>

        @if (filled($slug))
            <span class="hp-category-title__slug">/produk/{{ $slug }}</span>
        @else
            <span class="hp-category-title__slug hp-category-title__slug--missing">Slug belum diisi</span>
        @endif
    </span>
</span>
