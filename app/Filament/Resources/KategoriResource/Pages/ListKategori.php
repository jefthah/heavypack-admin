<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use App\Filament\Resources\ProdukResource;
use App\Filament\Support\ListLegacyRecords;
use App\Models\Kategori;
use App\Models\Produk;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListKategori extends ListLegacyRecords
{
    protected static string $resource = KategoriResource::class;

    /**
     * @var array{total: int, groups: int, empty: int, products: int}|null
     */
    protected ?array $cachedCategoryCounts = null;

    /**
     * Tautan "Atur urutan" di form kategori membuka daftar yang sudah disaring ke satu kelompok
     * dengan ?susun=1, jadi baris langsung bisa diseret tanpa klik "Susun ulang" dulu.
     */
    public function mount(): void
    {
        parent::mount();

        if (request()->boolean('susun') && static::getResource()::canCreate()) {
            $this->isTableReordering = true;
        }
    }

    public function getPageClasses(): array
    {
        return ['hp-list', 'hp-categories'];
    }

    public function getHeader(): ?View
    {
        $counts = $this->categoryCounts();

        return view('filament.kategori-header', [
            'metrics' => [
                [
                    'label' => 'Total kategori',
                    'value' => $this->formatCount($counts['total']),
                    'unit' => 'kategori',
                    'icon' => 'heroicon-o-squares-2x2',
                    'primary' => true,
                    'note' => $this->formatCount($counts['total'] - $counts['empty']).' sudah berisi produk',
                ],
                [
                    'label' => 'Kelompok mesin',
                    'value' => $this->formatCount($counts['groups']),
                    'unit' => 'kelompok',
                    'icon' => 'heroicon-o-folder',
                    'note' => $counts['groups'] > 0
                        ? 'Rata-rata '.number_format($counts['total'] / $counts['groups'], 1, ',', '.').' kategori'
                        : null,
                ],
                [
                    'label' => 'Belum ada produk',
                    'value' => $this->formatCount($counts['empty']),
                    'unit' => 'kategori',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'tone' => $counts['empty'] > 0 ? 'warning' : null,
                    'note' => $counts['empty'] > 0 ? null : 'Semua kategori sudah berisi produk.',
                    'url' => $counts['empty'] > 0 ? static::getResource()::getUrl('index', ['tab' => 'kosong']) : null,
                    'linkLabel' => 'Tampilkan',
                ],
                [
                    'label' => 'Produk dalam katalog',
                    'value' => $this->formatCount($counts['products']),
                    'unit' => 'produk',
                    'icon' => 'heroicon-o-cube',
                    'url' => ProdukResource::getUrl('index'),
                    'linkLabel' => 'Lihat produk',
                ],
            ],
        ]);
    }

    /**
     * Quick filters above the table: everything, categories in use, and empty ones to tidy up.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = $this->categoryCounts();

        return [
            'semua' => Tab::make('Semua')
                ->badge($this->formatCount($counts['total'])),
            'ada-produk' => Tab::make('Sudah ada produk')
                ->badge($this->formatCount($counts['total'] - $counts['empty']))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->has('products')),
            'kosong' => Tab::make('Belum ada produk')
                ->badge($this->formatCount($counts['empty']))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->doesntHave('products')),
        ];
    }

    /**
     * The tabs, search and group filter share the table card header, as on the product list.
     */
    public function table(Table $table): Table
    {
        return $table->header(fn (): View => view('filament.tables.list-tabs', [
            'tabs' => $this->getCachedTabs(),
            'activeTab' => (string) $this->activeTab,
            'label' => 'Filter cepat kategori',
        ]));
    }

    public function getTabsContentComponent(): Component
    {
        return parent::getTabsContentComponent()->hidden();
    }

    protected function getHeaderActions(): array
    {
        return static::getResource()::canCreate()
            ? [CreateAction::make()->label('Tambah kategori')->icon('heroicon-m-plus')]
            : [];
    }

    /**
     * @return array{total: int, groups: int, empty: int, products: int}
     */
    private function categoryCounts(): array
    {
        return $this->cachedCategoryCounts ??= [
            'total' => Kategori::count(),
            'groups' => Kategori::where('kategori', '!=', '')->distinct()->count('kategori'),
            'empty' => Kategori::doesntHave('products')->count(),
            'products' => Produk::count(),
        ];
    }

    private function formatCount(int $count): string
    {
        return number_format($count, 0, ',', '.');
    }
}
