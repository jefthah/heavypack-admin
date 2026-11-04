<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Alamat lama di website yang dialihkan permanen (301) ke alamat baru.
 *
 * Website membaca tabel ini setiap kali sebuah alamat tidak ditemukan (App\Support\Pengalihan
 * di repo heavypack). Admin mengisinya otomatis saat slug kategori atau produk berubah, dan
 * dari menu Pengaturan → Pengalihan alamat untuk pengalihan manual.
 */
class Pengalihan extends Model
{
    protected $connection = 'catalog';

    protected $table = 'pengalihan_alamat';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['terakhir_dipakai' => 'datetime', 'jumlah_dipakai' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $pengalihan): void {
            $pengalihan->dari = static::normalisasi($pengalihan->dari);
            if (! str_starts_with((string) $pengalihan->ke, 'http')) {
                $pengalihan->ke = static::normalisasi($pengalihan->ke);
            }
        });
    }

    /**
     * Path pembanding yang sama dengan website: huruf kecil, diawali "/", tanpa "/" di akhir dan tanpa query.
     */
    public static function normalisasi(?string $path): string
    {
        $path = (string) parse_url(trim((string) $path), PHP_URL_PATH);

        return mb_strtolower('/'.trim(rawurldecode($path), '/'));
    }

    /**
     * Catat bahwa $dari kini pindah ke $ke, misalnya ('/produk/slug-lama', '/produk/slug-baru').
     *
     * - Pengalihan lain yang menuju $dari ikut diarahkan ke $ke, jadi tidak ada rantai dua lompatan.
     * - Pengalihan dari $ke dihapus: alamat itu kini halaman sungguhan lagi (slug dikembalikan).
     *
     * Kegagalan database (mis. tabel belum dibuat) tidak menggagalkan penyimpanan data utama.
     */
    public static function catat(string $dari, string $ke, string $sumber): void
    {
        $dari = static::normalisasi($dari);
        $ke = static::normalisasi($ke);

        if ($dari === $ke || $dari === '/') {
            return;
        }

        try {
            DB::connection('catalog')->transaction(function () use ($dari, $ke, $sumber): void {
                static::query()->where('dari', $ke)->delete();
                static::query()->where('ke', $dari)->update(['ke' => $ke, 'updated_at' => now()]);
                static::query()->updateOrCreate(['dari' => $dari], ['ke' => $ke, 'sumber' => $sumber]);
            });
        } catch (QueryException $exception) {
            report($exception);
        }
    }
}
