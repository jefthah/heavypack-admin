<x-hp.list-header
    :breadcrumbs="[\App\Filament\Resources\ProdukResource::getUrl('index') => 'Produk', 'Kategori mesin']"
    heading="Kategori mesin"
    subheading="Susun kategori dengan rapi, bantu pelanggan menemukan mesin yang tepat."
    :actions="$this->getCachedHeaderActions()"
    :metrics="$metrics"
    metrics-label="Ringkasan kategori mesin"
/>
