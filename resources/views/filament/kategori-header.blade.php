<x-hp.list-header
    :breadcrumbs="[\App\Filament\Resources\ProdukResource::getUrl('index') => 'Produk', 'Kategori mesin']"
    heading="Kategori mesin"
    subheading="Susun kategori dengan rapi, bantu pelanggan menemukan mesin yang tepat."
    :actions="$this->getCachedHeaderActions()"
    :metrics="[
        [
            'label' => 'Total kategori',
            'value' => number_format($categoryCount, 0, ',', '.'),
            'unit' => 'kategori mesin',
            'icon' => 'heroicon-o-squares-2x2',
            'primary' => true,
        ],
        [
            'label' => 'Kelompok mesin',
            'value' => number_format($groupCount, 0, ',', '.'),
            'unit' => 'kelompok kategori',
            'icon' => 'heroicon-o-folder',
        ],
        [
            'label' => 'Produk dalam katalog',
            'value' => number_format($productCount, 0, ',', '.'),
            'unit' => 'produk mesin',
            'icon' => 'heroicon-o-cube',
        ],
    ]"
    metrics-label="Ringkasan katalog mesin"
/>
