<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use App\Models\Produk;
use App\Support\LegacyMedia;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProdukResource extends LegacyResource
{
    protected static ?string $model = Produk::class;

    protected static string $moduleKey = 'produk';

    public static function table(Table $table): Table
    {
        $image = collect(static::definition()['images'])->firstWhere('field', '_product_image');
        $gallery = static::definition()['gallery'];

        return parent::table($table)
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex()
                    ->extraAttributes(['class' => 'hp-product-no']),
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->color('gray')
                    ->tooltip('ID produk di database, sama dengan kolom No di admin lama.')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraAttributes(['class' => 'hp-product-id']),
                ImageColumn::make('preview_image')
                    ->label('Foto')
                    ->getStateUsing(fn (Produk $record): ?string => LegacyMedia::url($image, $record))
                    ->defaultImageUrl(asset('images/produk-placeholder.svg'))
                    ->imageSize(52)
                    ->extraImgAttributes(fn (Produk $record): array => ['alt' => $record->nama_produk, 'loading' => 'lazy'])
                    ->extraAttributes(['class' => 'hp-product-photo']),
                TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->formatStateUsing(fn (?string $state, Produk $record): HtmlString => new HtmlString(view('filament.tables.product-name', [
                        'name' => (string) $state,
                        'brand' => $record->brand,
                        'status' => $record->status_prod,
                        'missingGallery' => filled($record->status_prod) ? LegacyMedia::missingGalleryFiles($gallery, (string) $state) : [],
                        'galleryDirectory' => $gallery['directory'],
                    ])->render()))
                    ->description(fn (Produk $record): string => Str::limit((string) $record->judul_produk, 90))
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['nama_produk', 'judul_produk'])
                    ->sortable()
                    ->wrap()
                    ->extraAttributes(['class' => 'hp-product-name']),
                TextColumn::make('kategori.submenu')
                    ->label('Kategori')
                    ->placeholder('Tanpa kategori')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->visibleFrom('lg')
                    ->extraAttributes(['class' => 'hp-product-category']),
                TextColumn::make('harga_produk')
                    ->label('Harga')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? 'Rp '.number_format((float) $state, 0, ',', '.') : 'Belum diisi')
                    ->description(fn (Produk $record): ?string => $record->diskon_produk > 0 ? 'Diskon '.$record->diskon_produk.'%' : null)
                    ->color(fn ($state): ?string => (float) $state > 0 ? null : 'gray')
                    ->extraAttributes(fn (Produk $record): array => [
                        'class' => (float) $record->harga_produk > 0 ? 'hp-product-price' : 'hp-product-price hp-product-price--empty',
                    ]),
                TextColumn::make('jumlah_terjual')
                    ->label('Terjual')
                    ->numeric(locale: 'id')
                    ->description(fn (Produk $record): ?string => $record->rating > 0 ? '★ '.number_format((float) $record->rating, 1, ',', '.') : null)
                    ->sortable()
                    ->alignEnd()
                    ->visibleFrom('xl')
                    ->toggleable()
                    ->extraAttributes(['class' => 'hp-product-sales']),
                TextColumn::make('detail_produk')
                    ->label('Detail produk')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deskripsi_produk')
                    ->label('Spesifikasi')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('id_kategori')
                    ->label('Kategori')
                    ->placeholder('Semua kategori')
                    ->options(fn (): array => Kategori::orderBy('submenu')->pluck('submenu', 'id_kategoris')->all())
                    ->searchable()
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->hiddenLabel()),
                SelectFilter::make('brand')
                    ->label('Merek')
                    ->placeholder('Semua merek')
                    ->options(fn (): array => Produk::query()->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand', 'brand')->all())
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->hiddenLabel()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->deferFilters(false)
            ->filtersResetAction(fn (Action $action): Action => $action
                ->label('Atur ulang')
                ->icon('heroicon-m-x-mark')
                ->color('gray')
                ->hidden(fn (HasTable $livewire): bool => ! $livewire->getTable()->isFiltered()))
            ->searchPlaceholder('Cari tipe mesin, judul, atau kategori...')
            ->columnManagerTriggerAction(fn (Action $action): Action => $action
                ->iconButton()->icon('heroicon-m-view-columns')->label('Atur kolom')->tooltip('Atur kolom'))
            ->recordActions([
                EditAction::make()->label('Edit')->color('gray')->button()->outlined(),
                ActionGroup::make([
                    ViewAction::make()->label('Lihat detail'),
                    Action::make('viewOnWebsite')
                        ->label('Lihat di website')
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(fn (Produk $record): string => static::websiteUrl($record), shouldOpenInNewTab: true),
                    DeleteAction::make()
                        ->label('Hapus produk')
                        ->modalDescription('Hapus produk ini dari katalog? Data yang masih dipakai konten lain tidak dapat dihapus.'),
                ])->label('Aksi lainnya')->icon('heroicon-m-ellipsis-horizontal')->tooltip('Aksi lainnya'),
            ])
            ->recordActionsColumnLabel('Aksi')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateHeading('Produk tidak ditemukan')
            ->emptyStateDescription('Coba kata kunci, tab, atau filter lain, atau tambahkan produk baru.');
    }

    /**
     * Product detail page on the website that reads the active catalog.
     */
    public static function websiteUrl(Produk $record): string
    {
        $baseUrl = config('heavypack.content_mode') === 'shared'
            ? config('heavypack.website_url')
            : config('heavypack.preview_url');

        return rtrim($baseUrl, '/').'/detail/'.rawurlencode($record->nama_produk).'?record='.$record->getKey();
    }

    public static function getPages(): array
    {
        return [
            'index' => ProdukResource\Pages\ListProduk::route('/'),
            'create' => ProdukResource\Pages\CreateProduk::route('/create'),
            'edit' => ProdukResource\Pages\EditProduk::route('/{record}/edit'),
        ];
    }
}
