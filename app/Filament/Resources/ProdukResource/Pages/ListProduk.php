<?php

namespace App\Filament\Resources\ProdukResource\Pages;

use App\Filament\Resources\ProdukResource;
use App\Filament\Support\ListLegacyRecords;
use App\Models\Kategori;
use App\Models\Produk;
use App\Support\LegacyMedia;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListProduk extends ListLegacyRecords
{
    protected static string $resource = ProdukResource::class;

    /**
     * @var array{total: int, categories: int, withoutPrice: int, active: int, activeWithoutGallery: int, brands: array<string, int>}|null
     */
    protected ?array $cachedProductCounts = null;

    public function getPageClasses(): array
    {
        return ['hp-list', 'hp-products'];
    }

    public function getHeader(): ?View
    {
        $counts = $this->productCounts();

        return view('filament.produk-header', [
            'metrics' => [
                [
                    'label' => 'Total produk',
                    'value' => $this->formatCount($counts['total']),
                    'unit' => 'produk mesin',
                    'icon' => 'heroicon-o-cube',
                    'primary' => true,
                ],
                [
                    'label' => 'Kategori mesin',
                    'value' => $this->formatCount($counts['categories']),
                    'unit' => 'kategori',
                    'icon' => 'heroicon-o-squares-2x2',
                ],
                [
                    'label' => 'Merek',
                    'value' => $this->formatCount(count($counts['brands'])),
                    'unit' => 'merek produk',
                    'icon' => 'heroicon-o-tag',
                ],
            ],
        ]);
    }

    /**
     * Quick filters above the table, one per common task plus one per brand.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = $this->productCounts();

        $tabs = [
            'semua' => Tab::make('Semua')
                ->badge($this->formatCount($counts['total'])),
            'tanpa-harga' => Tab::make('Belum ada harga')
                ->badge($this->formatCount($counts['withoutPrice']))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('harga_produk', '<=', 0)),
            'aktif' => Tab::make('Status aktif')
                ->badge($this->formatCount($counts['active']))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status_prod', '!=', '')),
        ];

        foreach ($counts['brands'] as $brand => $count) {
            $tabs[Str::slug($brand)] = Tab::make($brand)
                ->badge($this->formatCount($count))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('brand', $brand));
        }

        return $tabs;
    }

    /**
     * The tabs, search and filters share the table card header instead of stacking separately.
     */
    public function table(Table $table): Table
    {
        return $table->header(fn (): View => view('filament.tables.list-tabs', [
            'tabs' => $this->getCachedTabs(),
            'activeTab' => (string) $this->activeTab,
            'label' => 'Filter cepat produk',
            'notice' => $this->productCounts()['activeWithoutGallery'] > 0
                ? $this->formatCount($this->productCounts()['activeWithoutGallery']).' produk aktif belum punya foto galeri.'
                : null,
        ]));
    }

    public function getTabsContentComponent(): Component
    {
        return parent::getTabsContentComponent()->hidden();
    }

    protected function getHeaderActions(): array
    {
        return static::getResource()::canCreate()
            ? [CreateAction::make()->label('Tambah produk')->icon('heroicon-m-plus')]
            : [];
    }

    /**
     * @return array{total: int, categories: int, withoutPrice: int, active: int, activeWithoutGallery: int, brands: array<string, int>}
     */
    private function productCounts(): array
    {
        if ($this->cachedProductCounts !== null) {
            return $this->cachedProductCounts;
        }

        $gallery = ProdukResource::definition()['gallery'];
        $activeNames = Produk::where('status_prod', '!=', '')->pluck('nama_produk');

        return $this->cachedProductCounts = [
            'total' => Produk::count(),
            'categories' => Kategori::count(),
            'withoutPrice' => Produk::where('harga_produk', '<=', 0)->count(),
            'active' => $activeNames->count(),
            'activeWithoutGallery' => $activeNames
                ->filter(fn (?string $name): bool => LegacyMedia::missingGalleryFiles($gallery, (string) $name) !== [])
                ->count(),
            'brands' => Produk::query()
                ->selectRaw('brand, COUNT(*) as total')
                ->where('brand', '!=', '')
                ->groupBy('brand')
                ->orderBy('brand')
                ->pluck('total', 'brand')
                ->map(fn (mixed $count): int => (int) $count)
                ->all(),
        ];
    }

    private function formatCount(int $count): string
    {
        return number_format($count, 0, ',', '.');
    }
}
