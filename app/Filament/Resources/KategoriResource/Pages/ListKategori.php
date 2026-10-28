<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use App\Filament\Support\ListLegacyRecords;
use App\Models\Kategori;
use App\Models\Produk;
use Filament\Actions\CreateAction;
use Illuminate\Contracts\View\View;

class ListKategori extends ListLegacyRecords
{
    protected static string $resource = KategoriResource::class;

    public function getPageClasses(): array
    {
        return ['hp-list', 'hp-categories'];
    }

    public function getHeader(): ?View
    {
        return view('filament.kategori-header', [
            'categoryCount' => Kategori::count(),
            'groupCount' => Kategori::where('kategori', '!=', '')->distinct()->count('kategori'),
            'productCount' => Produk::count(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return static::getResource()::canCreate()
            ? [CreateAction::make()->label('Tambah kategori')->icon('heroicon-m-plus')]
            : [];
    }
}
