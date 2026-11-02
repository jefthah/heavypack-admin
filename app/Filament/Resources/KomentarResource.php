<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Komentar;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KomentarResource extends LegacyResource
{
    protected static ?string $model = Komentar::class;

    protected static string $moduleKey = 'komentar';

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        // Kolom Artikel disisipkan sebelum Tanggal masuk.
        $columns = [];
        foreach ($table->getColumns() as $name => $column) {
            if ($name === 'created_at') {
                $columns[] = static::articleColumn();
            }
            $columns[] = $column;
        }

        return $table->columns($columns)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('artikel'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(static::getModelLabel())->schema([
                TextEntry::make('artikel.judul')->label('Artikel')
                    ->placeholder('Artikel sudah dihapus')
                    ->url(fn (Komentar $record): ?string => static::commentUrl($record), shouldOpenInNewTab: true)
                    ->color('primary')
                    ->icon('heroicon-m-arrow-top-right-on-square')->iconPosition(IconPosition::After),
                TextEntry::make('name')->label('Nama')->placeholder('—'),
                TextEntry::make('email')->label('Email')->placeholder('—'),
                TextEntry::make('isi_komentar')->label('Komentar')->placeholder('—')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    /**
     * Halaman artikel di website, langsung ke posisi komentar ini.
     */
    public static function commentUrl(Komentar $record): ?string
    {
        $slug = $record->artikel?->slug;

        return filled($slug)
            ? static::websiteLink('blog/'.rawurlencode($slug).'#komentar-'.$record->getKey())
            : null;
    }

    protected static function articleColumn(): TextColumn
    {
        return TextColumn::make('artikel.judul')->label('Artikel')
            ->placeholder('Artikel sudah dihapus')
            ->searchable()->sortable()->limit(40)->wrap()
            ->tooltip(fn (Komentar $record): ?string => $record->artikel?->judul)
            ->url(fn (Komentar $record): ?string => static::commentUrl($record), shouldOpenInNewTab: true)
            ->color('primary')
            ->icon('heroicon-m-arrow-top-right-on-square')->iconPosition(IconPosition::After);
    }

    public static function getPages(): array
    {
        return [
            'index' => KomentarResource\Pages\ListKomentar::route('/'),
        ];
    }
}
