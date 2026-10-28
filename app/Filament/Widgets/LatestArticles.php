<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ArtikelResource;
use App\Models\Artikel;
use App\Support\LegacyMedia;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestArticles extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 3];

    public function table(Table $table): Table
    {
        $image = collect(Artikel::definition()['images'])->firstWhere('field', 'gambar_cover');

        return $table
            ->heading('Artikel terbaru')
            ->description('Konten blog yang terakhir ditambahkan.')
            ->query(Artikel::query()->orderByDesc('id')->limit(5))
            ->columns([
                ImageColumn::make('preview_image')->label('Cover')
                    ->getStateUsing(fn (Artikel $record): ?string => LegacyMedia::url($image, $record))
                    ->imageHeight(40),
                TextColumn::make('judul')->label('Judul')->weight(FontWeight::SemiBold)->wrap()->lineClamp(2),
                TextColumn::make('kategori')->label('Kategori')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('created_at')->label('Ditambahkan')->since()->dateTimeTooltip('d M Y H:i')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('viewAll')->label('Lihat semua')->link()
                    ->icon('heroicon-m-arrow-right')->iconPosition(IconPosition::After)
                    ->url(ArtikelResource::getUrl('index')),
            ])
            ->recordUrl(fn (Artikel $record): ?string => ArtikelResource::canEdit($record) ? ArtikelResource::getUrl('edit', ['record' => $record]) : null)
            ->paginated(false)
            ->emptyStateHeading('Belum ada artikel');
    }
}
