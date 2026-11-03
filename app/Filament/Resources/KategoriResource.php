<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class KategoriResource extends LegacyResource
{
    protected static ?string $model = Kategori::class;

    protected static string $moduleKey = 'kategori';

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                TextColumn::make('id_kategoris')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submenu')
                    ->label('Nama kategori')
                    ->formatStateUsing(fn (?string $state, Kategori $record): HtmlString => new HtmlString(view('filament.tables.category-name', [
                        'name' => (string) $state,
                        'initials' => static::initials((string) $state),
                        'slug' => $record->url_submenu,
                    ])->render()))
                    ->searchable(['submenu', 'url_submenu'])
                    ->sortable()
                    ->wrap()
                    ->extraAttributes(['class' => 'hp-category-name']),
                TextColumn::make('kategori')
                    ->label('Kelompok mesin')
                    ->badge()
                    ->color('gray')
                    ->url(fn (Kategori $record): string => static::getUrl('index', [
                        'filters' => ['kategori' => ['value' => $record->kategori]],
                    ]))
                    ->tooltip('Tampilkan kategori di kelompok ini saja')
                    ->searchable(['kategori', 'url_kategori'])
                    ->sortable()
                    ->wrap()
                    ->extraAttributes(['class' => 'hp-category-group']),
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products')
                    ->formatStateUsing(fn (int $state): string => $state > 0
                        ? number_format($state, 0, ',', '.').' produk'
                        : 'Belum ada produk')
                    ->icon(fn (int $state): ?string => $state > 0 ? null : 'heroicon-m-exclamation-triangle')
                    ->color(fn (int $state): ?string => $state > 0 ? null : 'warning')
                    ->sortable()
                    ->url(fn (Kategori $record): ?string => $record->products_count > 0
                        ? ProdukResource::getUrl('index', ['filters' => ['id_kategori' => ['value' => $record->getKey()]]])
                        : null)
                    ->tooltip(fn (Kategori $record): string => $record->products_count > 0
                        ? 'Lihat produk dalam kategori ini'
                        : 'Kategori ini belum dipakai produk mana pun')
                    ->extraAttributes(fn (Kategori $record): array => [
                        'class' => $record->products_count > 0 ? 'hp-category-products' : 'hp-category-products hp-category-products--empty',
                    ]),
                TextColumn::make('url_kategori')
                    ->label('Slug kelompok')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kelompok mesin')
                    ->placeholder('Semua kelompok')
                    ->options(fn (): array => Kategori::query()->where('kategori', '!=', '')->distinct()->orderBy('kategori')->pluck('kategori', 'kategori')->all())
                    ->searchable()
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->hiddenLabel()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->filtersResetAction(fn (Action $action): Action => $action
                ->label('Atur ulang')
                ->icon('heroicon-m-x-mark')
                ->color('gray')
                ->hidden(fn (HasTable $livewire): bool => ! $livewire->getTable()->isFiltered()))
            ->searchPlaceholder('Cari nama, kelompok, atau slug...')
            ->columnManagerTriggerAction(fn (Action $action): Action => $action
                ->iconButton()->icon('heroicon-m-view-columns')->label('Atur kolom')->tooltip('Atur kolom'))
            ->recordActions([
                EditAction::make()->label('Edit')->color('gray')->button()->outlined(),
                ActionGroup::make([
                    ViewAction::make()->label('Lihat detail'),
                    Action::make('viewOnWebsite')
                        ->label('Lihat di website')
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(fn (Kategori $record): string => static::websiteLink('produk/'.rawurlencode($record->url_submenu)), shouldOpenInNewTab: true)
                        ->visible(fn (Kategori $record): bool => filled($record->url_submenu)),
                    DeleteAction::make()
                        ->label('Hapus kategori')
                        ->modalDescription('Hapus kategori ini dari katalog? Kategori yang masih dipakai produk atau sparepart tidak dapat dihapus.'),
                ])->label('Aksi lainnya')->icon('heroicon-m-ellipsis-horizontal')->tooltip('Aksi lainnya'),
            ])
            ->recordActionsColumnLabel('Aksi')
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-squares-2x2')
            ->emptyStateHeading('Kategori tidak ditemukan')
            ->emptyStateDescription('Coba kata kunci, tab, atau kelompok lain, atau tambahkan kategori baru.');
    }

    /**
     * Two-letter mark for the row avatar, e.g. "Double Chamber Vacuum Machine" -> "DC".
     */
    public static function initials(string $name): string
    {
        return Str::of($name)->squish()->explode(' ')->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
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
