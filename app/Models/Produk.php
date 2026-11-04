<?php

namespace App\Models;

use Illuminate\Support\Str;


class Produk extends LegacyRecord
{
    protected static string $moduleKey = 'produk';

    protected static function booted(): void
    {
        parent::booted();

        // Alamat /detail/{slug_produk}: terisi dari nama bila kosong, dan unik (akhiran -2, -3, ...).
        static::saving(function (self $produk): void {
            if (blank($produk->slug_produk)) {
                $produk->slug_produk = static::slugUnik((string) $produk->nama_produk, $produk->getKey());
            }
        });

        // Slug diganti: alamat lama dialihkan ke alamat baru di website.
        static::updated(function (self $produk): void {
            if ($produk->wasChanged('slug_produk') && filled($produk->getOriginal('slug_produk'))) {
                Pengalihan::catat('/detail/'.$produk->getOriginal('slug_produk'), '/detail/'.$produk->slug_produk, 'produk');
            }
        });
    }

    /**
     * Slug dari nama produk yang belum dipakai produk lain, sama dengan aturan SQL di
     * database/sql/2026_11_04_pengamanan_alamat.sql repo website.
     */
    public static function slugUnik(string $nama, mixed $kecualiId = null): string
    {
        $dasar = Str::slug($nama) ?: 'produk';
        $dipakai = static::query()
            ->where(fn ($query) => $query->where('slug_produk', $dasar)->orWhere('slug_produk', 'like', $dasar.'-%'))
            ->when($kecualiId, fn ($query) => $query->whereKeyNot($kecualiId))
            ->pluck('slug_produk')
            ->all();

        $slug = $dasar;
        for ($urutan = 2; in_array($slug, $dipakai, true); $urutan++) {
            $slug = $dasar.'-'.$urutan;
        }

        return $slug;
    }
}
