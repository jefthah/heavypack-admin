<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProdukResource;
use App\Models\Produk;
use App\Support\LegacyMedia;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

class LatestProducts extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 3];

    public function table(Table $table): Table
    {
        $image = collect(Produk::definition()['images'])->firstWhere('field', '_product_image');

        return $table
            ->heading('Produk terbaru')
            ->description('Mesin yang terakhir ditambahkan ke katalog.')
            ->query(Produk::query()->with('kategori')->orderByDesc('id')->limit(5))
            ->columns([
                ImageColumn::make('preview_image')->label('Foto')
                    ->getStateUsing(fn (Produk $record): ?string => LegacyMedia::url($image, $record))
                    ->imageHeight(40),
                TextColumn::make('nama_produk')->label('Produk')->weight(FontWeight::SemiBold)
                    ->description(fn (Produk $record): string => Str::limit((string) $record->judul_produk, 48)),
                TextColumn::make('kategori.submenu')->label('Kategori')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('harga_produk')->label('Harga')->alignEnd()->placeholder('—')
                    ->formatStateUsing(fn ($state): string => $state > 0 ? 'Rp '.number_format((float) $state, 0, ',', '.') : '—'),
            ])
            ->headerActions([
                Action::make('viewAll')->label('Lihat semua')->link()
                    ->icon('heroicon-m-arrow-right')->iconPosition(IconPosition::After)
                    ->url(ProdukResource::getUrl('index')),
            ])
            ->recordUrl(fn (Produk $record): ?string => ProdukResource::canEdit($record) ? ProdukResource::getUrl('edit', ['record' => $record]) : null)
            ->paginated(false)
            ->emptyStateHeading('Belum ada produk');
    }
}
