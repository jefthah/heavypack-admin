<?php

namespace App\Filament\Resources;

use App\Filament\Support\LegacyResource;
use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Menu katalog = kelompok mesin di filter website. Daftarnya (MenuResource\Pages\ListMenu) bukan tabel
 * bawaan, melainkan daftar ringkas per brand yang barisnya langsung bisa diseret untuk mengubah urutan.
 */
class MenuResource extends LegacyResource
{
    protected static ?string $model = Menu::class;

    protected static string $moduleKey = 'menu';

    /** @var array<string, array{jenis: int, tampil: int, mesin: int, teks: string}>|null */
    protected static ?array $cachedIsi = null;

    /**
     * Kelompok yang tampil di brand Heavypack di website: semua yang bukan Heavypro
     * (brand kosong atau tidak dikenal juga masuk Heavypack, sama seperti MachineDirectory).
     */
    public static function brandHeavypack(Builder $query): Builder
    {
        return $query->where(fn (Builder $brand): Builder => $brand->where('brand', '!=', 'heavypro')->orWhereNull('brand'));
    }

    /** Kelompok satu brand dengan urutan yang sama seperti filter website: kolom urutan, lalu id. */
    public static function kelompokBrand(string $brand): Builder
    {
        $query = Menu::query();
        $brand === 'heavypro' ? $query->where('brand', 'heavypro') : static::brandHeavypack($query);

        return $query->orderBy('urutan')->orderBy('id');
    }

    /**
     * Isi kelompok: jumlah jenis mesin, jenis yang berisi mesin (yang tampil di filter website),
     * jumlah mesin, dan teks "Kelompok kategori" untuk menyaring daftar Kategori mesin.
     *
     * @return array{jenis: int, tampil: int, mesin: int, teks: string}
     */
    public static function isi(Menu $record): array
    {
        static::$cachedIsi ??= DB::connection((new Kategori)->getConnectionName())
            ->table('kategoris')
            ->leftJoin('produks', 'produks.id_kategori', '=', 'kategoris.id_kategoris')
            ->selectRaw('TRIM(kategoris.url_kategori) AS kelompok, COUNT(DISTINCT kategoris.id_kategoris) AS jenis, '
                .'COUNT(DISTINCT CASE WHEN produks.id IS NOT NULL THEN kategoris.id_kategoris END) AS tampil, '
                .'COUNT(produks.id) AS mesin, MIN(kategoris.kategori) AS teks')
            ->groupByRaw('TRIM(kategoris.url_kategori)')
            ->get()
            ->mapWithKeys(fn (object $baris): array => [(string) $baris->kelompok => [
                'jenis' => (int) $baris->jenis,
                'tampil' => (int) $baris->tampil,
                'mesin' => (int) $baris->mesin,
                'teks' => (string) $baris->teks,
            ]])
            ->all();

        return static::$cachedIsi[trim((string) $record->url_menu)] ?? ['jenis' => 0, 'tampil' => 0, 'mesin' => 0, 'teks' => ''];
    }

    /** Kelompok tampil di filter website bila minimal satu jenis mesinnya berisi mesin. */
    public static function tampil(Menu $record): bool
    {
        return static::isi($record)['tampil'] > 0;
    }

    /** Daftar Kategori mesin yang disaring ke kelompok ini, tempat jenisnya bisa disusun ulang. */
    public static function alamatJenis(Menu $record): ?string
    {
        $teks = static::isi($record)['teks'];

        return $teks !== '' ? KategoriResource::getUrl('index', ['filters' => ['kategori' => ['value' => $teks]]]) : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => MenuResource\Pages\ListMenu::route('/'),
            'create' => MenuResource\Pages\CreateMenu::route('/create'),
            'edit' => MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
