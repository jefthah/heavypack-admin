<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use App\Models\Sparepart;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class KategoriResource extends LegacyResource
{
    protected static ?string $model = Kategori::class;

    protected static string $moduleKey = 'kategori';

    /**
     * Same suffix the website adds to every category page title.
     */
    public const TITLE_SUFFIX = ' | Heavypack Indonesia';

    /**
     * The form explains where each field appears on the website, with a live preview beside it.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make('Informasi kategori')
                    ->description('Nama yang tampil di menu dan katalog website.')
                    ->schema([
                        TextInput::make('kategori')
                            ->label('Kelompok kategori')
                            ->required()
                            ->maxLength(200)
                            ->datalist(fn (): array => static::groupNames())
                            ->dehydrateStateUsing(fn (?string $state): string => static::existingGroupName((string) $state))
                            ->helperText('Pilih kelompok yang sudah ada agar kategori ini tampil bersama kategori sejenis.'),
                        TextInput::make('submenu')
                            ->label('Nama kategori')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                                if ($operation === 'create' && blank($get('url_submenu'))) {
                                    $set('url_submenu', Str::slug((string) $state));
                                }
                            }),
                    ])
                    ->columns(2),
                Section::make('Alamat di website')
                    ->description('Bagian akhir alamat halaman. Spasi dan huruf besar dirapikan otomatis saat kolom ditinggalkan.')
                    ->schema([
                        static::slugInput('url_submenu', 'Slug kategori', 'produk')
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'Slug ini sudah dipakai kategori lain.'])
                            ->helperText('Alamat halaman kategori ini. Harus berbeda dari kategori lain.'),
                        static::slugInput('url_kategori', 'Slug kelompok', 'product')
                            ->required()
                            ->helperText('Halaman kelompok tempat kategori ini ikut tampil.'),
                        static::slugInput('url_sparepart', 'Slug sparepart', 'sparepart')
                            ->helperText('Boleh sama dengan kategori lain; sparepart-nya lalu tampil di satu halaman. Kosongkan bila belum ada.'),
                    ]),
                Section::make('Teks halaman & Google')
                    ->description('Judul dan deskripsi halaman kategori, juga dipakai Google di hasil pencarian.')
                    ->schema([
                        TextInput::make('judul')
                            ->label('Judul halaman')
                            ->maxLength(200)
                            ->live(debounce: 500)
                            ->hint(fn (?string $state): string => mb_strlen((string) $state).' karakter')
                            ->helperText('Tampil di tab browser dan sebagai judul di Google, yang biasanya hanya memuat ±60 karakter.'),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(8)
                            ->live(debounce: 500)
                            ->hint(fn (?string $state): string => mb_strlen(static::plainText($state)).' karakter teks')
                            ->helperText('Hanya ±180 karakter pertama yang tampil di halaman kategori, dan ±155 di Google. Kode HTML seperti <b> dibuang, jadi tulis kalimat terpenting di awal.'),
                    ]),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('Pratinjau di website')
                    ->description('Berubah otomatis saat Anda mengetik.')
                    ->schema([
                        View::make('filament.forms.category-preview')
                            ->viewData(fn (Get $get): array => static::previewData(
                                (string) $get('submenu'),
                                (string) $get('url_submenu'),
                                (string) $get('judul'),
                                (string) $get('deskripsi'),
                            )),
                    ]),
                Section::make('Dipakai di katalog')
                    ->schema([
                        View::make('filament.forms.category-usage')
                            ->viewData(fn (?Kategori $record): array => $record ? static::usageData($record) : []),
                    ])
                    ->visibleOn('edit'),
            ])->columnSpan(['lg' => 1])->extraAttributes(['class' => 'hp-form-aside']),
        ])->columns(3);
    }

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

    /**
     * Slug field shown after its website path, tidied into a valid slug when the field loses focus.
     */
    protected static function slugInput(string $name, string $label, string $path): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(200)
            ->prefix('/'.$path.'/')
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(['regex' => 'Gunakan huruf kecil, angka, dan tanda hubung saja.'])
            ->live(onBlur: true)
            ->afterStateUpdated(fn (?string $state, Set $set) => $set($name, Str::slug((string) $state)));
    }

    /**
     * Existing machine groups, trimmed, for the group field's suggestions.
     *
     * @return array<int, string>
     */
    public static function groupNames(): array
    {
        return static::storedGroupNames()
            ->map(fn (string $group): string => static::trimGroup($group))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Legacy group names carry trailing spaces ("Mesin Sealer  ", "Mesin Filling" + a non-breaking space).
     * A stored spelling is kept as it is; a newly typed name reuses the stored spelling instead of
     * starting a second group that only differs in spacing or case.
     */
    public static function existingGroupName(string $group): string
    {
        $stored = static::storedGroupNames();

        if ($stored->contains(fn (string $existing): bool => $existing === $group)) {
            return $group;
        }

        $key = Str::lower(static::trimGroup($group));

        // Several spellings can match; the one most categories use wins.
        return $stored->filter(fn (string $existing): bool => Str::lower(static::trimGroup($existing)) === $key)
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first() ?? static::trimGroup($group);
    }

    /**
     * Group name of every category, compared in PHP: MySQL's collation treats "Mesin Sealer" and "Mesin Sealer  " as one value.
     *
     * @return Collection<int, string>
     */
    protected static function storedGroupNames(): Collection
    {
        return Kategori::query()->where('kategori', '!=', '')->pluck('kategori');
    }

    protected static function trimGroup(string $group): string
    {
        return preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $group);
    }

    /**
     * Text a visitor reads once the website strips the HTML from a description.
     */
    public static function plainText(?string $html): string
    {
        return Str::squish(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * What the category page and its Google result show, cut the way the website and Google cut them.
     *
     * @return array<string, string|null>
     */
    public static function previewData(string $name, string $slug, string $title, string $description): array
    {
        $url = filled($slug) ? static::websiteLink('produk/'.$slug) : null;
        $site = static::websiteLink('produk');
        $text = static::plainText($description);

        return [
            'url' => $url,
            'host' => parse_url($site, PHP_URL_HOST).(parse_url($site, PHP_URL_PORT) ? ':'.parse_url($site, PHP_URL_PORT) : ''),
            'slug' => $slug,
            'name' => trim($name),
            'title' => filled($title) ? Str::limit(trim($title).static::TITLE_SUFFIX, 60) : null,
            'snippet' => filled($text) ? Str::limit($text, 155) : null,
            'intro' => filled($text) ? Str::limit($text, 180) : null,
        ];
    }

    /**
     * Products and spare parts that use the category, with links to those lists.
     *
     * @return array<string, int|string|null>
     */
    public static function usageData(Kategori $record): array
    {
        return [
            'products' => $record->products()->count(),
            'productsUrl' => ProdukResource::getUrl('index', ['filters' => ['id_kategori' => ['value' => $record->getKey()]]]),
            'spareparts' => Sparepart::where('id_kategori', $record->getKey())->count(),
            'sparepartsUrl' => SparepartResource::getUrl('index', ['filters' => ['id_kategori' => ['value' => $record->getKey()]]]),
            'websiteUrl' => filled($record->url_submenu) ? static::websiteLink('produk/'.rawurlencode($record->url_submenu)) : null,
        ];
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
