<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'akun-admin';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'Akun admin';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Akun admin';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
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
        return static::canViewAny() && auth()->id() !== $record->id
            && (! $record->isSuperAdmin() || User::whereKeyNot($record->id)->where('role', 'super_admin')->where('is_active', true)->exists());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('email')->label('Email login')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            Select::make('role')->label('Hak akses')->options(['super_admin' => 'Super admin', 'editor' => 'Editor konten'])->default('editor')->required(),
            Toggle::make('is_active')->label('Akun aktif')->default(true),
            TextInput::make('password')->label('Password')->password()->revealable()->minLength(12)->maxLength(128)
                ->required(fn (string $operation) => $operation === 'create')->dehydrated(fn (?string $state) => filled($state))
                ->helperText('Minimal 12 karakter. Saat mengedit, biarkan kosong untuk mempertahankan password.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('email')->label('Email')->searchable(),
            TextColumn::make('role')->label('Hak akses')->badge(),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->recordActions([EditAction::make()->label('Edit'), DeleteAction::make()->label('Hapus')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'create' => UserResource\Pages\CreateUser::route('/create'),
            'edit' => UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
