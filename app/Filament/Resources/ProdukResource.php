<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Produk;
use App\Support\LegacyMedia;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProdukResource extends LegacyResource
{
    protected static ?string $model = Produk::class;

    protected static string $moduleKey = 'produk';

    /** Merek yang dikenal website (Produk::brandLabel dan filter brand di halaman /mesin). */
    public const MEREK = ['HEAVYPACK' => 'Heavypack', 'HEAVYPRO' => 'Heavypro'];

    /**
     * Form bawaan (LegacyResource::form) dengan Kategori dikelompokkan per kelompok Menu katalog,
     * Merek berupa pilihan yang terisi dari Kategori, dan keterangan rak brand di website.
     */
    protected static function formField(string $name, Field $component): array
    {
        return match ($name) {
            // Judul selebar form, supaya Kategori dan Merek berdampingan.
            'judul_produk' => [$component->columnSpanFull()],
            'id_kategori' => [static::kategoriSelect($component)],
            'brand' => [
                static::merekSelect(),
                View::make('filament.forms.product-brand-shelves')
                    ->viewData(fn (Get $get): array => static::rakWebsite($get('brand'), static::kelompokKategori($get('id_kategori'))))
                    ->columnSpanFull(),
            ],
            default => [$component],
        };
    }

    protected static function kategoriSelect(Select $select): Select
    {
        return $select
            ->options(fn (): array => static::kategoriOptions())
            ->placeholder('Pilih kategori')
            ->helperText('Dikelompokkan per kelompok Menu katalog, dengan brand tempat kelompok itu tampil di website.')
            ->live()
            ->afterStateUpdated(function (mixed $state, Set $set): void {
                // Merek mengikuti brand kelompoknya, supaya mesin tampil di rak yang sama dengan kategorinya.
                if ($kelompok = static::kelompokKategori($state)) {
                    $set('brand', $kelompok['brand'] === 'heavypro' ? 'HEAVYPRO' : 'HEAVYPACK');
                }
            });
    }
    /**
     * Kategori dikelompokkan per kelompok Menu katalog, urut seperti filter website (Heavypack,
     * lalu Heavypro, lalu kelompok yang belum terdaftar). Judul kelompok menyebut brand-nya,
     * mis. "Carton Handling Packaging · Heavypack + Industries".
     *
     * @return array<string, array<int, string>>
     */
    public static function kategoriOptions(): array
    {
        $menus = Menu::query()->orderBy('urutan')->orderBy('id')->get()->keyBy(fn (Menu $menu): string => trim((string) $menu->url_menu));
        $posisi = $menus->keys()->flip();

        $options = [];
        Kategori::query()
            ->orderBy('urutan')->orderBy('id_kategoris')
            ->get(['id_kategoris', 'kategori', 'url_kategori', 'submenu'])
            ->groupBy(fn (Kategori $kategori): string => trim((string) $kategori->url_kategori))
            ->sortBy(function (Collection $daftar, string $url) use ($menus, $posisi): string {
                $menu = $menus->get($url);
                $rak = $menu ? ($menu->brand === 'heavypro' ? 1 : 0) : 2;

                return sprintf('%d-%05d-%s', $rak, $posisi->get($url, 0), $url);
            })
            ->each(function (Collection $daftar, string $url) use ($menus, &$options): void {
                $menu = $menus->get($url);
                $judul = $menu
                    ? trim((string) $menu->menu).' · '.($menu->brand === 'heavypro' ? 'Heavypro' : 'Heavypack'.($menu->industri ? ' + Industries' : ''))
                    : (trim((string) $daftar->first()->kategori) ?: ($url ?: 'Tanpa kelompok')).' · belum di Menu katalog';
                $options[$judul] = ($options[$judul] ?? []) + $daftar->pluck('submenu', 'id_kategoris')->all();
            });

        return $options;
    }

    /**
     * Kelompok (Menu katalog) sebuah kategori, seperti dibaca website (MachineDirectory::groups):
     * brand selain heavypro dianggap heavypack, kelompok yang belum terdaftar ikut Heavypack.
     *
     * @return array{nama: string, brand: 'heavypack'|'heavypro', industri: bool, terdaftar: bool}|null
     */
    public static function kelompokKategori(mixed $idKategori): ?array
    {
        $kategori = filled($idKategori) ? Kategori::find($idKategori) : null;
        if (! $kategori) {
            return null;
        }

        $url = trim((string) $kategori->url_kategori);
        $menu = $url !== '' ? Menu::query()->where('url_menu', $url)->first() : null;

        return [
            'nama' => trim((string) ($menu?->menu ?: $kategori->kategori)) ?: $url,
            'brand' => $menu?->brand === 'heavypro' ? 'heavypro' : 'heavypack',
            'industri' => (bool) $menu?->industri,
            'terdaftar' => $menu !== null,
        ];
    }

    /**
     * Merek berupa pilihan, karena website hanya mengenal HEAVYPACK dan HEAVYPRO. Nilai lama di luar
     * itu tetap terlihat, tetapi harus diganti sebelum disimpan.
     */
    protected static function merekSelect(): Select
    {
        return Select::make('brand')
            ->label('Merek')
            ->options(function (?string $state): array {
                $lama = trim((string) $state);

                return static::MEREK + ($lama !== '' && ! isset(static::MEREK[$lama]) ? [$lama => $lama.' (tidak dikenal website)'] : []);
            })
            ->afterStateHydrated(function (Select $component, ?string $state): void {
                $merek = strtoupper(trim((string) $state));
                if (isset(static::MEREK[$merek])) {
                    $component->state($merek);
                }
            })
            ->required()
            ->in(array_keys(static::MEREK))
            ->validationMessages(['in' => 'Pilih Heavypack atau Heavypro.'])
            ->placeholder('Terisi otomatis dari kategori')
            ->live()
            ->helperText('Terisi otomatis sesuai brand kelompok kategori. Heavypack Industries bukan merek; lihat keterangan di bawah.');
    }

    /**
     * Rak brand di halaman /mesin website yang memuat mesin ini, sama dengan filter brand website
     * (MachineDirectory::filterBrand): Heavypack = Merek HEAVYPACK; Heavypro = Merek HEAVYPRO, atau
     * Merek kosong di kelompok Heavypro; Heavypack Industries = Merek HEAVYPACK di kelompok yang
     * dicentang "Juga di Heavypack Industries".
     *
     * @param  array{nama: string, brand: string, industri: bool, terdaftar: bool}|null  $kelompok
     * @return array{rak: array<string, string>|null, catatan: list<string>, peringatan: ?string}  rak null = kategori belum dipilih
     */
    public static function rakWebsite(mixed $merek, ?array $kelompok): array
    {
        $merek = strtoupper(trim((string) $merek));
        $rak = [];
        $catatan = [];
        $peringatan = null;

        if ($merek === 'HEAVYPACK') {
            $rak['heavypack'] = 'Heavypack';
            if ($kelompok['industri'] ?? false) {
                $rak['industries'] = 'Heavypack Industries';
            }
        } elseif ($merek === 'HEAVYPRO' || ($merek === '' && ($kelompok['brand'] ?? null) === 'heavypro')) {
            $rak['heavypro'] = 'Heavypro';
        }

        if ($kelompok === null) {
            return ['rak' => null, 'catatan' => ['Pilih kategori untuk melihat rak brand tempat mesin ini tampil di website.'], 'peringatan' => null];
        }

        $nama = '"'.$kelompok['nama'].'"';
        if (! $kelompok['terdaftar']) {
            $catatan[] = 'Kelompok '.$nama.' belum ada di Menu katalog, jadi website menaruhnya di Heavypack (paling bawah) dan tidak di Heavypack Industries.';
        } elseif ($merek === 'HEAVYPACK' && $kelompok['brand'] === 'heavypack') {
            $catatan[] = $kelompok['industri']
                ? 'Ikut tampil di Heavypack Industries karena kelompok '.$nama.' dicentang "Juga di Heavypack Industries" di Menu katalog.'
                : 'Tidak tampil di Heavypack Industries karena kelompok '.$nama.' tidak dicentang "Juga di Heavypack Industries" di Menu katalog.';
        }

        $brandKelompok = $kelompok['brand'] === 'heavypro' ? 'Heavypro' : 'Heavypack';
        if ($merek === 'HEAVYPACK' && $kelompok['brand'] === 'heavypro' || $merek === 'HEAVYPRO' && $kelompok['brand'] === 'heavypack') {
            $peringatan = 'Merek '.static::MEREK[$merek].' tidak sama dengan kelompok '.$nama.', yang tampil di bawah '.$brandKelompok.' di website. '
                .'Mesin ini akan muncul di rak '.static::MEREK[$merek].', padahal kategorinya ada di daftar '.$brandKelompok.'. '
                .'Ganti Merek menjadi '.$brandKelompok.', atau pindahkan kelompoknya di Menu katalog.';
        } elseif ($merek !== '' && ! isset(static::MEREK[$merek])) {
            $peringatan = 'Merek "'.$merek.'" tidak dikenal website: mesin ini hanya muncul di "Semua mesin" dan halaman kategorinya.';
        }

        return compact('rak', 'catatan', 'peringatan');
    }


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
        return filled($record->slug_produk)
            ? static::websiteLink('detail/'.$record->slug_produk)
            : static::websiteLink('detail/'.rawurlencode($record->nama_produk).'?record='.$record->getKey());
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
