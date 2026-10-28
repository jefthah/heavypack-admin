<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KategoriResource extends LegacyResource
{
    protected static ?string $model = Kategori::class;

    protected static string $moduleKey = 'kategori';

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->heading('Daftar kategori')
            ->columns([
                TextColumn::make('id_kategoris')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submenu')
                    ->label('Nama kategori')
                    ->description(fn (Kategori $record): string => $record->url_submenu ?: 'Slug belum diisi')
                    ->icon('heroicon-o-squares-2x2')
                    ->iconColor('primary')
                    ->weight(FontWeight::Medium)
                    ->searchable(['submenu', 'url_submenu'])
                    ->sortable()
                    ->wrap()
                    ->extraAttributes(['class' => 'hp-category-name']),
                TextColumn::make('kategori')
                    ->label('Kelompok mesin')
                    ->badge()
                    ->color('gray')
                    ->searchable(['kategori', 'url_kategori'])
                    ->sortable()
                    ->wrap()
                    ->extraAttributes(['class' => 'hp-category-group']),
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->numeric(locale: 'id')
                    ->suffix(' produk')
                    ->sortable()
                    ->color('gray')
                    ->url(fn (Kategori $record): string => ProdukResource::getUrl('index', [
                        'filters' => ['id_kategori' => ['value' => $record->getKey()]],
                    ]))
                    ->tooltip('Lihat produk dalam kategori ini')
                    ->extraAttributes(['class' => 'hp-category-products']),
                TextColumn::make('url_kategori')
                    ->label('Slug kelompok')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->searchPlaceholder('Cari nama, kelompok, atau slug...')
            ->filtersTriggerAction(fn (Action $action): Action => $action
                ->button()->label('Filter kelompok')->icon('heroicon-m-adjustments-horizontal'))
            ->columnManagerTriggerAction(fn (Action $action): Action => $action
                ->button()->label('Kolom')->icon('heroicon-m-view-columns'))
            ->recordActions([
                EditAction::make()->label('Edit')->color('gray')->button()->outlined(),
                ActionGroup::make([
                    ViewAction::make()->label('Lihat detail'),
                    DeleteAction::make()
                        ->label('Hapus kategori')
                        ->modalDescription('Hapus kategori ini dari katalog? Kategori yang masih dipakai produk atau sparepart tidak dapat dihapus.'),
                ])->label('Aksi lainnya')->icon('heroicon-m-ellipsis-horizontal')->tooltip('Aksi lainnya'),
            ])
            ->recordActionsColumnLabel('Aksi')
            ->defaultPaginationPageOption(10)
            ->emptyStateIcon('heroicon-o-squares-2x2')
            ->emptyStateHeading('Kategori tidak ditemukan')
            ->emptyStateDescription('Coba kata kunci atau filter lain, atau tambahkan kategori baru.');
    }

    public static function getPages(): array
    {
        return [
            'index' => KategoriResource\Pages\ListKategori::route('/'),
            'create' => KategoriResource\Pages\CreateKategori::route('/create'),
            'edit' => KategoriResource\Pages\EditKategori::route('/{record}/edit'),
        ];
    }
}
