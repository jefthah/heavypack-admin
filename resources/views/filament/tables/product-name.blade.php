<span class="hp-product-title">
    <span class="hp-product-title__name">{{ $name }}</span>

    @if (filled($brand))
        <span class="hp-pill hp-pill--brand hp-pill--{{ \Illuminate\Support\Str::slug($brand) }}">{{ $brand }}</span>
    @endif

    @if (filled($status))
        <span class="hp-pill hp-pill--status" title="Halaman detail menampilkan galeri foto tambahan.">{{ $status }}</span>
    @endif

    @if (filled($missingGallery))
        <span class="hp-pill hp-pill--warning" title="Belum ada di folder {{ $galleryDirectory }}: {{ implode(', ', $missingGallery) }}">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" />
            Foto galeri belum ada
        </span>
    @endif
</span>
