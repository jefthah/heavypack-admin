<?php

namespace App\Filament\Support;

use App\Models\Artikel;
use App\Models\Kategori;
use App\Models\KategoriArtikel;
use App\Support\LegacyMedia;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

abstract class LegacyResource extends Resource
{
    protected static string $moduleKey;

    public static function definition(): array
    {
        return config('legacy.'.static::$moduleKey);
    }

    public static function getModelLabel(): string
    {
        return static::definition()['label'];
    }

    public static function getPluralModelLabel(): string
    {
        return static::definition()['label'];
    }

    public static function getNavigationLabel(): string
    {
        return static::definition()['label'];
    }

    public static function getNavigationGroup(): ?string
    {
        return static::definition()['group'];
    }

    public static function getNavigationIcon(): ?string
    {
        return static::definition()['icon'];
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return static::definition()['title'];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [static::definition()['title']];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && $user->is_active && in_array($user->role, ['super_admin', 'editor'], true);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && ! (static::definition()['readOnly'] ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() && (static::definition()['allowDelete'] ?? true) && ! $record->hasDependentRecords();
    }

    public static function form(Schema $schema): Schema
    {
        $definition = static::definition();
        $fields = [];
        foreach ($definition['fields'] as $field) {
            $name = $field['name'];
            $component = match ($field['type']) {
                'textarea' => Textarea::make($name)->rows(5)->columnSpanFull(),
                'rich' => RichEditor::make($name)->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'blockquote', 'undo', 'redo'])->columnSpanFull(),
                'date' => DatePicker::make($name),
                'category' => Select::make($name)->options(fn () => Kategori::orderBy('submenu')->pluck('submenu', 'id_kategoris')->all())->searchable()->preload(),
                'articleCategory' => Select::make($name)->options(fn () => collect(KategoriArtikel::pluck('kategori'))->merge(Artikel::distinct()->pluck('kategori'))->filter()->unique()->mapWithKeys(fn ($value) => [$value => $value])->all())->searchable(),
                'galleryToggle' => static::galleryToggle($name, $field['onValue'], $definition['gallery']),
                default => TextInput::make($name),
            };
            $component->label($field['label']);
            $image = collect($definition['images'] ?? [])->firstWhere('field', $name);
            if ($field['required'] ?? false) {
                $component->required($image && ($image['url'] ?? false) ? fn (Get $get) => ! $get('_upload_'.$name) : true);
            }
            if (isset($field['max']) && in_array($field['type'], ['text', 'url', 'textarea'], true)) {
                $component->maxLength($field['max']);
            }
            if ($field['type'] === 'number') {
                $component->numeric()->default(0)->minValue($field['min'] ?? 0);
                if (isset($field['maxValue'])) {
                    $component->maxValue($field['maxValue']);
                }
                if ($name !== 'rating') {
                    $component->rule('integer');
                }
            }
            if ($field['type'] === 'url') {
                $component->url();
            }
            if ($field['unique'] ?? false) {
                $component->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, ?Model $record, Get $get) use ($name): Unique {
                    // Existing duplicate machine names are part of the legacy catalog.
                    if ($record && $get($name) === $record->getAttribute($name)) {
                        $rule->where(fn ($query) => $query->whereRaw('1 = 0'));
                    }

                    return $rule;
                });
            }
            if (isset($field['note'])) {
                $component->helperText($field['note']);
            }
            if (static::$moduleKey === 'artikel' && $name === 'judul') {
                $component->live(onBlur: true)->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                    if ($operation === 'create' && ! $get('slug')) {
                        $set('slug', Str::slug($state ?? ''));
                    }
                });
            }
            $fields[] = $component;
        }
        $sections = [Section::make($definition['label'])->schema($fields)->columns(2)->columnSpanFull()];
        $uploads = [];
        foreach ($definition['images'] ?? [] as $image) {
            $upload = FileUpload::make('_upload_'.ltrim($image['field'], '_'))->label($image['label'])
                ->disk('legacy_uploads')->directory('_uploads')->visibility('private')->maxSize(20480)
                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => Str::uuid().'.'.$file->guessExtension());
            if ($image['extension'] === 'pdf') {
                $upload->acceptedFileTypes(['application/pdf'])->helperText('Upload PDF atau isi tautan unduhan pada formulir. Maksimal 20 MB.');
            } else {
                $upload->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(10240)
                    ->helperText('Opsional. Upload untuk mengganti gambar; JPG, PNG, atau WebP. Maksimal 10 MB dan 6000 × 6000 piksel.');
            }
            $uploads[] = $upload;
        }
        if ($uploads) {
            $sections[] = Section::make('Upload media')->schema($uploads)->columns(2)->columnSpanFull();
        }

        return $schema->components($sections);
    }

    /**
     * Switch for a legacy text flag that turns on the website gallery, warning when its photos are missing.
     *
     * @param  array{directory: string, namingField: string, suffixes: array<int, string>, extension: string}  $gallery
     */
    protected static function galleryToggle(string $name, string $onValue, array $gallery): Toggle
    {
        $missingFiles = fn (Get $get): array => $get($name)
            ? LegacyMedia::missingGalleryFiles($gallery, (string) $get($gallery['namingField']))
            : [];

        return Toggle::make($name)
            ->onColor('primary')
            ->live()
            ->formatStateUsing(fn (?string $state): bool => filled($state))
            ->dehydrateStateUsing(fn (mixed $state): string => $state ? $onValue : '')
            ->hint(fn (Get $get): ?string => $missingFiles($get) ? 'Foto galeri belum lengkap' : null)
            ->hintIcon(fn (Get $get): ?string => $missingFiles($get) ? 'heroicon-m-exclamation-triangle' : null)
            ->hintColor('warning')
            ->helperText(function (Get $get) use ($gallery, $missingFiles): string {
                $missing = $missingFiles($get);

                if ($missing) {
                    return 'Belum ada di folder '.$gallery['directory'].': '.implode(', ', $missing).'. Thumbnail galeri di website akan rusak sampai foto ini diupload.';
                }

                $files = LegacyMedia::galleryFiles($gallery, (string) ($get($gallery['namingField']) ?: 'nama mesin'));

                return 'Jika aktif, halaman detail di website juga menampilkan '.implode(', ', $files).' dari folder '.$gallery['directory'].'.';
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        $entries = [];
        foreach (static::definition()['fields'] as $field) {
            $entry = TextEntry::make($field['type'] === 'category' ? 'kategori.submenu' : $field['name'])->label($field['label'])->placeholder('—');
            if (in_array($field['type'], ['textarea', 'rich'], true)) {
                $entry->columnSpanFull();
            }
            if ($field['type'] === 'rich') {
                $entry->html();
            }
            if ($field['type'] === 'galleryToggle') {
                $entry->getStateUsing(fn (Model $record): string => filled($record->getAttribute($field['name'])) ? 'Ya' : 'Tidak');
            }
            $entries[] = $entry;
        }

        return $schema->components([Section::make(static::getModelLabel())->schema($entries)->columns(2)->columnSpanFull()]);
    }

    public static function table(Table $table): Table
    {
        $definition = static::definition();
        $labels = collect($definition['fields'])->pluck('label', 'name')->all();
        $columns = [TextColumn::make($definition['pk'])->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true)];
        $image = collect($definition['images'] ?? [])->first(fn ($image) => ! ($image['url'] ?? false));
        if ($image) {
            $columns[] = ImageColumn::make('preview_image')->label('Gambar')->getStateUsing(fn (Model $record) => LegacyMedia::url($image, $record))->imageHeight(48);
        }
        foreach ($definition['columns'] as $name) {
            $column = TextColumn::make($name)->label($labels[$name] ?? ($name === 'kategori.submenu' ? 'Kategori' : 'Tanggal masuk'))
                ->searchable(! in_array($name, ['created_at', 'harga_produk', 'tanggal_beli'], true))->sortable()->limit(65)->wrap();
            if ($name === 'harga_produk') {
                $column->money('IDR');
            }
            if ($name === 'created_at') {
                $column->dateTime('d M Y H:i');
            }
            $columns[] = $column;
        }
        $filters = [];
        foreach ($definition['filters'] ?? [] as $field) {
            if ($field === 'category') {
                $filters[] = SelectFilter::make('id_kategori')->label('Kategori')->options(fn () => Kategori::orderBy('submenu')->pluck('submenu', 'id_kategoris')->all())->searchable();
            } else {
                $filters[] = SelectFilter::make($field)->label($labels[$field] ?? Str::headline($field))
                    ->options(fn () => static::getModel()::query()->where($field, '!=', '')->distinct()->orderBy($field)->pluck($field, $field)->all())->searchable();
            }
        }
        $actions = [ViewAction::make()->label('Lihat')];
        if (! ($definition['readOnly'] ?? false)) {
            $actions[] = EditAction::make()->label('Edit');
        }
        if ($definition['allowDelete'] ?? true) {
            $actions[] = DeleteAction::make()->label('Hapus')->modalDescription('Hapus data ini dari katalog? Data yang masih dipakai konten lain tidak dapat dihapus.');
        }

        return $table->columns($columns)->filters($filters)->recordActions($actions)
            ->defaultSort($definition['pk'], $definition['sortDirection'] ?? 'desc')->paginated([10, 25, 50, 100])->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada data')->emptyStateDescription('Data akan muncul di sini setelah ditambahkan atau diterima melalui website.');
    }
}
