<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Pengalihan;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar alamat lama website yang dialihkan ke alamat baru (tabel pengalihan_alamat).
 * Sebagian besar terisi otomatis saat slug kategori atau produk diubah.
 */
class PengalihanResource extends Resource
{
    protected static ?string $model = Pengalihan::class;

    protected static ?string $slug = 'pengalihan-alamat';

    protected static ?string $recordTitleAttribute = 'dari';

    private const SUMBER = [
        'kategori' => 'Slug kategori diubah',
        'menu' => 'Slug menu diubah',
        'produk' => 'Slug produk diubah',
        'perbaikan' => 'Perbaikan alamat',
        'manual' => 'Ditambah manual',
    ];

    public static function getModelLabel(): string
    {
        return 'Pengalihan alamat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengalihan alamat';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-uturn-right';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && $user->is_active && in_array($user->role, ['super_admin', 'editor'], true);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pengalihan alamat')
                ->description('Pengunjung dan Google yang membuka alamat lama langsung dibawa ke alamat baru (pengalihan permanen 301).')
                ->schema([
                    TextInput::make('dari')
                        ->label('Alamat lama')
                        ->placeholder('/produk/nama-lama')
                        ->required()
                        ->maxLength(191)
                        // Dibandingkan setelah dirapikan, karena "/Produk/Lama/" dan "/produk/lama" alamat yang sama.
                        ->rule(fn (?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $dari = Pengalihan::normalisasi($value);
                            if ($dari === '/') {
                                $fail('Beranda tidak bisa dialihkan.');
                            } elseif (Pengalihan::query()->where('dari', $dari)->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))->exists()) {
                                $fail('Alamat lama ini sudah punya pengalihan.');
                            }
                        })
                        ->dehydrateStateUsing(fn (?string $state): string => Pengalihan::normalisasi($state))
                        ->helperText('Bagian alamat setelah heavypack.id, misalnya /produk/nama-lama. Huruf besar-kecil tidak dibedakan.'),
                    TextInput::make('ke')
                        ->label('Alamat baru')
                        ->placeholder('/produk/nama-baru')
                        ->required()
                        ->maxLength(191)
                        ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if (! str_starts_with((string) $value, 'http') && Pengalihan::normalisasi($value) === Pengalihan::normalisasi($get('dari'))) {
                                $fail('Alamat baru tidak boleh sama dengan alamat lama.');
                            }
                        })
                        ->helperText('Alamat di heavypack.id (/produk/nama-baru) atau alamat lengkap ke situs lain (https://...).'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dari')->label('Alamat lama')->searchable()->sortable()->copyable()->wrap(),
                TextColumn::make('ke')->label('Dialihkan ke')->searchable()->wrap()
                    ->url(fn (Pengalihan $record): string => str_starts_with($record->ke, 'http') ? $record->ke : LegacyResource::websiteLink($record->ke), shouldOpenInNewTab: true),
                TextColumn::make('sumber')->label('Asal')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $state): string => self::SUMBER[$state] ?? (string) $state),
                TextColumn::make('jumlah_dipakai')->label('Dipakai')->numeric(locale: 'id')->sortable()->alignEnd()
                    ->tooltip('Berapa kali alamat lama ini dibuka sejak dicatat.'),
                TextColumn::make('terakhir_dipakai')->label('Terakhir dipakai')->since()->sortable()->placeholder('Belum pernah'),
                TextColumn::make('created_at')->label('Dicatat')->date('j M Y')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()->label('Edit'), DeleteAction::make()->label('Hapus')])
            ->emptyStateIcon('heroicon-o-arrow-uturn-right')
            ->emptyStateHeading('Belum ada pengalihan')
            ->emptyStateDescription('Pengalihan tercatat otomatis saat slug kategori atau produk diubah.');
    }

    public static function getPages(): array
    {
        return [
            'index' => PengalihanResource\Pages\ListPengalihan::route('/'),
            'create' => PengalihanResource\Pages\CreatePengalihan::route('/create'),
            'edit' => PengalihanResource\Pages\EditPengalihan::route('/{record}/edit'),
        ];
    }
}
