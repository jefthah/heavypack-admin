<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Sparepart;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
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
     * Google biasanya memuat ±60 karakter judul, termasuk akhiran TITLE_SUFFIX dari website.
     */
    public const TITLE_LIMIT = 60;

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
                            ->helperText(new HtmlString('Kelompok baru dibuat di <a href="'.e(MenuResource::getUrl('index')).'" class="hp-helper-link">Menu katalog</a>.')),
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
                        // Urutan tidak diketik lagi: kategori baru (atau yang pindah kelompok) otomatis paling akhir
                        // di kelompoknya (Kategori::booted), dan urutannya diatur dengan menyeret di daftar.
                        View::make('filament.forms.category-position')
                            ->viewData(fn (?Kategori $record, Get $get): array => static::positionData($record, (string) $get('url_kategori')))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Alamat di website')
                    ->description('Huruf besar dan spasi dirapikan otomatis.')
                    ->schema([
                        static::slugInput('url_submenu', 'Slug kategori', 'mesin')
                            ->prefix(fn (Get $get): string => '/mesin/'.(trim((string) $get('url_kategori')) ?: '…').'/')
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'Slug ini sudah dipakai kategori lain.'])
                            ->helperText('Harus berbeda dari kategori lain. Bila diganti, alamat lama otomatis dialihkan.'),
                        static::slugInput('url_sparepart', 'Slug sparepart', 'sparepart')
                            ->helperText('Boleh sama dengan kategori lain (sparepart-nya digabung di satu halaman). Kosongkan bila belum ada.'),
                    ]),
                Section::make('Teks halaman & Google')
                    ->description('Tampil di halaman kategori dan di hasil pencarian Google.')
                    ->schema([
                        TextInput::make('judul')
                            ->label('Judul halaman')
                            ->maxLength(200)
                            ->live(debounce: 500)
                            ->hint(fn (?string $state): HtmlString => static::titleHint($state))
                            ->helperText('Tampil di tab browser dan sebagai judul di Google. Website menambahkan "'.trim(static::TITLE_SUFFIX).'" di belakangnya.'),
                        RichEditor::make('deskripsi')
                            ->label('Deskripsi')
                            ->tools([
                                // Satu tombol subjudul saja: website menampilkan semua tingkat judul dengan gaya yang sama.
                                RichEditorTool::make('subjudul')
                                    ->label('Subjudul')
                                    ->jsHandler('$getEditor()?.chain().focus().toggleHeading({ level: 3 }).run()')
                                    ->toggle()
                                    ->activeKey('heading')
                                    ->icon('fi-o-h3'),
                            ])
                            // Hanya format yang ditampilkan website (App\Support\TeksKatalog di repo website):
                            // garis bawah dan tautan dibuang di sana, jadi tombolnya tidak disediakan.
                            ->toolbarButtons([['bold', 'italic'], ['subjudul', 'bulletList', 'orderedList'], ['undo', 'redo']])
                            ->formatStateUsing(fn (mixed $state): ?string => static::collapseWhitespace(static::descriptionHtml($state)))
                            ->live(debounce: 800)
                            ->hint(fn (mixed $state): string => number_format(mb_strlen(static::plainText(static::descriptionHtml($state))), 0, ',', '.').' karakter')
                            ->helperText('Tulis kalimat terpenting di awal: ±155 karakter pertama dipakai Google dan ±180 menjadi ringkasan di atas halaman. Teks lengkapnya tampil di bagian "Tentang …" di bawah daftar mesin.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => static::tidyDescription($state))
                            ->extraAttributes(['class' => 'hp-desc-editor']),
                    ]),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('Pratinjau')
                    ->description('Berubah otomatis saat Anda mengetik.')
                    ->schema([
                        View::make('filament.forms.category-preview')
                            ->viewData(fn (Get $get): array => static::previewData(
                                (string) $get('submenu'),
                                (string) $get('url_kategori'),
                                (string) $get('url_submenu'),
                                (string) $get('judul'),
                                static::descriptionHtml($get('deskripsi')),
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
        $table = parent::table($table)
            ->columns([
                TextColumn::make('id_kategoris')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->tooltip('Urutan di kelompoknya pada filter website. Angka kecil tampil lebih dulu.')
                    ->sortable()
                    ->toggleable(),
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
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->hiddenLabel()
                        ->helperText('Pilih satu kelompok untuk bisa menyusun urutannya.')),
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

        // Urutan berlaku per kelompok, jadi tombol Susun ulang baru muncul setelah satu kelompok dipilih;
        // tanpa itu kategori dari semua kelompok bercampur dalam satu daftar. Saat satu kelompok dipilih,
        // daftar langsung tampil menurut urutan website; tanpa kelompok, yang terbaru di atas.
        return static::susunUlang($table, 'urutan', fn (HasTable $livewire): bool => static::kelompokDipilih($livewire))
            ->defaultSort(
                fn (HasTable $livewire): string => static::kelompokDipilih($livewire) ? 'urutan' : 'id_kategoris',
                fn (HasTable $livewire): string => static::kelompokDipilih($livewire) ? 'asc' : 'desc',
            );
    }

    /** Apakah daftar sedang disaring ke satu kelompok mesin (filter "Kelompok mesin"). */
    protected static function kelompokDipilih(HasTable $livewire): bool
    {
        return filled($livewire->getTableFilterState('kategori')['value'] ?? null);
    }

    /**
     * Two-letter mark for the row avatar, e.g. "Double Chamber Vacuum Machine" -> "DC".
     * Words that do not start with a letter or digit are skipped: "Shrink & L-Side Sealing" -> "SL".
     */
    public static function initials(string $name): string
    {
        return Str::of($name)->squish()->explode(' ')
            ->filter(fn (string $word): bool => (bool) preg_match('/^[\pL\pN]/u', $word))
            ->take(2)
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
     * Text a visitor reads once the website strips the HTML from a description
     * (same as TeksKatalog::ringkas on the website: a space before every tag keeps words apart).
     */
    public static function plainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::squish(str_replace("\u{00A0}", ' ', $text));
    }

    /**
     * The description as HTML. Inside the form the rich editor keeps its content as an editor document
     * (an array), so the live preview and the character count convert it first.
     */
    public static function descriptionHtml(mixed $state): string
    {
        if (blank($state)) {
            return '';
        }

        return is_array($state) ? RichContentRenderer::make($state)->toHtml() : (string) $state;
    }

    /**
     * Older descriptions were typed as indented HTML with line breaks inside sentences. A browser joins those
     * lines, but the editor shows each one as a separate line, so whitespace is collapsed the way a browser
     * shows it before the editor opens: runs of spaces become one, and spaces next to block tags are dropped.
     */
    public static function collapseWhitespace(string $html): ?string
    {
        // Kosong (termasuk "<p></p>"): null, supaya editor mulai dari dokumen kosong bawaannya.
        if (static::plainText($html) === '') {
            return null;
        }

        $html = preg_replace('/[ \t\r\n]+/', ' ', $html);
        $html = preg_replace('#\s*(</?(?:p|ul|ol|li|h[1-6]|br|div|blockquote)\b[^>]*>)\s*#i', '$1', $html);

        // Paragraf kosong tidak tampil di website (TeksKatalog membuangnya), jadi tidak perlu ada di editor.
        return trim(preg_replace('#<p>(\s|&nbsp;|\x{00A0})*</p>#u', '', $html));
    }

    /**
     * HTML from the rich editor, tidied before it is saved:
     * - "<li><p>…</p></li>" becomes "<li>…</li>" (the editor wraps every list item in a paragraph,
     *   which on the website adds paragraph spacing inside lists);
     * - an empty editor ("<p></p>") is saved as empty.
     */
    public static function tidyDescription(?string $html): ?string
    {
        $html = trim((string) $html);
        if (static::plainText($html) === '') {
            return null;
        }

        return preg_replace('#<li>\s*<p>(.*?)</p>\s*</li>#su', '<li>$1</li>', $html);
    }

    /**
     * Character count beside "Judul halaman", warning once the title plus the website suffix
     * is longer than what Google usually shows.
     */
    public static function titleHint(?string $title): HtmlString
    {
        $length = mb_strlen(trim((string) $title));
        $room = static::TITLE_LIMIT - mb_strlen(static::TITLE_SUFFIX);

        if ($length > $room) {
            return new HtmlString('<span class="hp-count hp-count--over">'.$length.' / '.$room.' · terpotong di Google</span>');
        }

        return new HtmlString('<span class="hp-count">'.$length.' / '.$room.'</span>');
    }

    /**
     * Where the category sits in its group on the website filter, shown instead of a number field.
     * Order follows the website (MachineDirectory): urutan, then id.
     *
     * @return array<string, mixed>
     */
    public static function positionData(?Kategori $record, string $group): array
    {
        $group = trim($group);
        if ($group === '') {
            return ['mode' => 'empty'];
        }

        $groupName = Menu::options()[$group] ?? $group;

        // Kategori baru, atau kelompoknya baru diganti: setelah disimpan ditaruh paling akhir di kelompok itu.
        if (! $record || $group !== trim((string) $record->url_kategori)) {
            return [
                'mode' => $record ? 'move' : 'new',
                'groupName' => $groupName,
                'position' => Kategori::query()->where('url_kategori', $group)->count() + 1,
            ];
        }

        $ids = Kategori::query()
            ->where('url_kategori', $group)
            ->get(['id_kategoris', 'urutan'])
            ->sortBy(fn (Kategori $kategori): string => sprintf('%010d-%010d', (int) $kategori->urutan, $kategori->id_kategoris))
            ->pluck('id_kategoris')
            ->values();

        return [
            'mode' => 'current',
            'groupName' => $groupName,
            'position' => $ids->search($record->getKey()) + 1,
            'total' => $ids->count(),
            'hasProducts' => $record->products()->exists(),
            'url' => static::canCreate()
                ? static::getUrl('index', ['filters' => ['kategori' => ['value' => $record->kategori]], 'susun' => 1])
                : null,
        ];
    }

    /**
     * Description HTML as the website shows it in "Tentang …" (TeksKatalog::html on the website):
     * only p, ul, ol, li, strong, b, em, i and br without attributes, every heading as h3,
     * other tags unwrapped and their text kept.
     */
    public static function websiteHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        $result = $root ? static::websiteNodes($root) : '';
        $result = preg_replace('#<(p|li|h3|strong|b|em|i)>(\s|\x{00A0})*</\1>#u', '', $result);

        return trim(preg_replace('#<(ul|ol)>\s*</\1>#u', '', $result));
    }

    private static function websiteNodes(DOMNode $node): string
    {
        $result = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $result .= e($child->textContent);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript'], true)) {
                continue;
            }

            $inner = static::websiteNodes($child);
            $result .= match (true) {
                $tag === 'br' => '<br>',
                (bool) preg_match('/^h[1-6]$/', $tag) => '<h3>'.$inner.'</h3>',
                in_array($tag, ['p', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i'], true) => '<'.$tag.'>'.$inner.'</'.$tag.'>',
                default => $inner,
            };
        }

        return $result;
    }

    /**
     * What the category page and its Google result show, cut the way the website and Google cut them.
     *
     * @return array<string, string|bool|null>
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
            'groupName' => Menu::options()[trim($group)] ?? null,
            'slug' => $slug,
            'name' => trim($name),
            'title' => filled($title) ? Str::limit(trim($title).static::TITLE_SUFFIX, static::TITLE_LIMIT) : null,
            'snippet' => filled($text) ? Str::limit($text, 155) : null,
            'intro' => filled($text) ? Str::limit($text, 180) : null,
            // Website hanya menampilkan "Tentang …" bila teksnya lebih panjang dari ringkasan di atas.
            'about' => mb_strlen($text) > 180 ? static::websiteHtml($description) : null,
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
