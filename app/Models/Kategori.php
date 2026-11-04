<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends LegacyRecord
{
    protected static string $moduleKey = 'kategori';

    /**
     * Halaman website yang alamatnya diambil dari kolom kategori ini.
     * 'unik' = hanya satu kategori yang memakainya (halaman jenis mesin); yang lain bisa dipakai bersama.
     */
    private const ALAMAT = [
        'url_submenu' => ['path' => 'produk', 'unik' => true],
        'url_kategori' => ['path' => 'product', 'unik' => false],
        'url_sparepart' => ['path' => 'sparepart', 'unik' => false],
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Produk::class, 'id_kategori', 'id_kategoris');
    }

    protected static function booted(): void
    {
        parent::booted();

        // Slug diganti: alamat lama dialihkan ke alamat baru. Untuk halaman bersama (kelompok, sparepart)
        // hanya bila tidak ada kategori lain yang masih memakai slug lama, karena halamannya masih ada.
        static::updated(function (self $kategori): void {
            foreach (self::ALAMAT as $kolom => $alamat) {
                $lama = trim((string) $kategori->getOriginal($kolom));
                $baru = trim((string) $kategori->getAttribute($kolom));

                if (! $kategori->wasChanged($kolom) || $lama === '' || $baru === '') {
                    continue;
                }
                if (! $alamat['unik'] && static::query()->where($kolom, $lama)->exists()) {
                    continue;
                }

                Pengalihan::catat('/'.$alamat['path'].'/'.$lama, '/'.$alamat['path'].'/'.$baru, 'kategori');
            }
        });
    }
}
