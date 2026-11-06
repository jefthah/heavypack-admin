<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends LegacyRecord
{
    protected static string $moduleKey = 'kategori';

    /**
     * Halaman bersama yang alamatnya diambil dari kolom kategori ini: dipakai banyak kategori, jadi
     * alamat lamanya hanya dialihkan bila tidak ada kategori lain yang masih memakainya.
     * Halaman kategori sendiri (/mesin/{url_kategori}/{url_submenu}) diurus terpisah di booted().
     */
    private const ALAMAT_BERSAMA = [
        'url_kategori' => 'mesin',
        'url_sparepart' => 'sparepart',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Produk::class, 'id_kategori', 'id_kategoris');
    }

    /**
     * Alamat halaman kategori ini di website (tanpa domain): mesin/{kelompok}/{kategori},
     * atau null bila kelompok atau slug belum diisi.
     */
    public function alamatWebsite(): ?string
    {
        return static::alamat((string) $this->url_kategori, (string) $this->url_submenu);
    }

    /** mesin/{kelompok}/{kategori} dari dua slug, atau null bila salah satunya kosong. */
    public static function alamat(string $kelompok, string $kategori): ?string
    {
        $kelompok = trim($kelompok);
        $kategori = trim($kategori);

        return $kelompok !== '' && $kategori !== '' ? 'mesin/'.rawurlencode($kelompok).'/'.rawurlencode($kategori) : null;
    }

    protected static function booted(): void
    {
        parent::booted();

        // Teks "Kelompok kategori" selalu sama dengan nama Menu katalog yang dipilih
        // (tampil sebagai label kartu produk di beranda website).
        static::saving(function (self $kategori): void {
            $menu = Menu::query()->where('url_menu', $kategori->url_kategori)->value('menu');
            if (filled($menu)) {
                $kategori->kategori = trim($menu);
            } elseif (blank($kategori->kategori)) {
                $kategori->kategori = (string) $kategori->url_kategori;
            }
        });

        // Slug atau kelompok diganti: alamat lama dialihkan ke alamat baru.
        static::updated(function (self $kategori): void {
            $lama = fn (string $kolom): string => trim((string) $kategori->getOriginal($kolom));
            $baru = fn (string $kolom): string => trim((string) $kategori->getAttribute($kolom));

            // Halaman kategori /mesin/{kelompok}/{kategori} pindah bila slug-nya atau kelompoknya berubah.
            // Pengalihan lain yang menuju alamat lama (termasuk /produk/... dari sebelum Paket 3) ikut diarahkan.
            if ($kategori->wasChanged(['url_kategori', 'url_submenu'])) {
                $dari = static::alamat($lama('url_kategori'), $lama('url_submenu'));
                $ke = static::alamat($baru('url_kategori'), $baru('url_submenu'));
                if ($dari && $ke) {
                    Pengalihan::catat('/'.$dari, '/'.$ke, 'kategori');
                }
            }

            // Halaman bersama (kelompok, sparepart) hanya dialihkan bila tidak ada kategori lain yang masih
            // memakai slug lama, karena selama itu halamannya masih ada.
            foreach (self::ALAMAT_BERSAMA as $kolom => $path) {
                if (! $kategori->wasChanged($kolom) || $lama($kolom) === '' || $baru($kolom) === '') {
                    continue;
                }
                if (static::query()->where($kolom, $lama($kolom))->exists()) {
                    continue;
                }

                Pengalihan::catat('/'.$path.'/'.$lama($kolom), '/'.$path.'/'.$baru($kolom), 'kategori');
            }
        });
    }
}
