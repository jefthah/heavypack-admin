<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use App\Filament\Support\EditLegacyRecord;
use App\Models\Kategori;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditKategori extends EditLegacyRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewOnWebsite')
                ->label('Lihat di website')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->outlined()
                ->url(fn (Kategori $record): string => KategoriResource::websiteLink((string) $record->alamatWebsite()), shouldOpenInNewTab: true)
                ->visible(fn (Kategori $record): bool => $record->alamatWebsite() !== null),
            // Outlined so the destructive action is not the loudest button on the page.
            DeleteAction::make()
                ->label('Hapus')
                ->outlined()
                ->modalDescription('Hapus kategori ini dari katalog? Kategori yang masih dipakai produk atau sparepart tidak dapat dihapus.'),
        ];
    }
}
