<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use App\Models\Menu;
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
                        Select::make('url_kategori')
                            ->label('Kelompok')
                            ->options(fn (?Kategori $record): array => static::groupOptions($record))
                            ->required()
                            ->searchable()
                            ->preload()
                            // Alamat kategori ikut kelompoknya (/mesin/{kelompok}/{kategori}): pratinjau diperbarui.
                            ->live()
                            ->helperText(new HtmlString('Daftar dari <a href="'.e(MenuResource::getUrl('index')).'" class="hp-helper-link">Menu katalog</a>. Nama, brand, dan urutan kelompok diatur di sana.')),
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
                        TextInput::make('urutan')
                            ->label('Urutan di kelompok')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(fn (): int => (int) Kategori::query()->max('urutan') + 1)
                            ->helperText('Urutan di filter dan tombol jenis mesin di website. Angka kecil tampil lebih dulu. Kategori tanpa produk tidak tampil di filter.'),
                    ])
                    ->columns(2),
                Section::make('Alamat di website')
                    ->description('Bagian akhir alamat halaman. Spasi dan huruf besar dirapikan otomatis saat kolom ditinggalkan.')
                    ->schema([
                        static::slugInput('url_submenu', 'Slug kategori', 'mesin')
                            ->prefix(fn (Get $get): string => '/mesin/'.(trim((string) $get('url_kategori')) ?: '…').'/')
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'Slug ini sudah dipakai kategori lain.'])
                            ->helperText('Alamat halaman kategori ini, di bawah alamat kelompoknya. Harus berbeda dari kategori lain. Bila slug atau kelompok diganti, alamat lama dialihkan otomatis.'),
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
                                (string) $get('url_kategori'),
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
                        'group' => trim((string) $record->url_kategori),
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
                        ->url(fn (Kategori $record): string => static::websiteLink((string) $record->alamatWebsite()), shouldOpenInNewTab: true)
                        ->visible(fn (Kategori $record): bool => $record->alamatWebsite() !== null),
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
     * Kelompok yang bisa dipilih: Menu katalog menurut urutan filter. Kelompok lama yang belum
     * terdaftar di Menu katalog tetap tersedia untuk kategori yang memakainya, supaya tidak hilang saat disimpan.
     *
     * @return array<string, string>
     */
    public static function groupOptions(?Kategori $record = null): array
    {
        $options = Menu::options();
        $current = (string) $record?->url_kategori;

        if ($current !== '' && ! array_key_exists($current, $options)) {
            $options[$current] = (trim((string) $record->kategori) ?: $current).' (belum ada di Menu katalog)';
        }

        return $options;
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
    public static function previewData(string $name, string $group, string $slug, string $title, string $description): array
    {
        $path = Kategori::alamat($group, $slug);
        $url = $path ? static::websiteLink($path) : null;
        $site = static::websiteLink('mesin');
        $text = static::plainText($description);

        return [
            'url' => $url,
            'host' => parse_url($site, PHP_URL_HOST).(parse_url($site, PHP_URL_PORT) ? ':'.parse_url($site, PHP_URL_PORT) : ''),
            'group' => trim($group),
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
            'websiteUrl' => $record->alamatWebsite() ? static::websiteLink($record->alamatWebsite()) : null,
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
