<x-hp.list-header
    :breadcrumbs="[\App\Filament\Resources\ProdukResource::getUrl('index') => 'Produk', 'Produk mesin']"
    heading="Produk mesin"
    subheading="Kelola mesin yang tampil di katalog website HeavyPack."
    :actions="$this->getCachedHeaderActions()"
    :metrics="$metrics"
    metrics-label="Ringkasan katalog"
/>
