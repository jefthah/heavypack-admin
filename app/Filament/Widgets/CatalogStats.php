<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ArtikelResource;
use App\Filament\Resources\KatalogResource;
use App\Filament\Resources\KategoriResource;
use App\Filament\Resources\LayananResource;
use App\Filament\Resources\ProdukResource;
use App\Filament\Resources\SparepartResource;
use App\Filament\Support\LegacyResource;
use Filament\Widgets\Widget;

class CatalogStats extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.catalog-stats';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'stats' => [
                $this->stat(ProdukResource::class, 'Katalog mesin'),
                $this->stat(KategoriResource::class, 'Kelompok mesin'),
                $this->stat(SparepartResource::class, 'Suku cadang'),
                $this->stat(ArtikelResource::class, 'Konten & insight'),
                $this->stat(KatalogResource::class, 'Dokumen unduhan'),
                $this->stat(LayananResource::class, 'Pesan dari website'),
            ],
        ];
    }

    /**
     * @param  class-string<LegacyResource>  $resource
     * @return array{label: string, value: string, description: string, icon: ?string, url: ?string}
     */
    private function stat(string $resource, string $description): array
    {
        return [
            'label' => $resource::getNavigationLabel(),
            'value' => number_format($resource::getModel()::count(), 0, ',', '.'),
            'description' => $description,
            'icon' => $resource::getNavigationIcon(),
            'url' => $resource::canViewAny() ? $resource::getUrl('index') : null,
        ];
    }
}
