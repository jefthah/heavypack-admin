<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu katalog = kelompok mesin di website (filter, label kartu beranda, halaman /mesin/{url_menu}).
 */
class Menu extends LegacyRecord
{
    protected static string $moduleKey = 'menu';

    public function categories(): HasMany
    {
        return $this->hasMany(Kategori::class, 'url_kategori', 'url_menu');
    }

    /**
     * Pilihan kelompok untuk form kategori: [url_menu => nama menu], menurut urutan filter.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::query()->orderBy('urutan')->orderBy('id')->get()
            ->mapWithKeys(fn (self $menu): array => [$menu->url_menu => trim((string) $menu->menu)])
            ->all();
    }

    protected static function booted(): void
    {
        // parent::booted() lebih dulu: slug yang diganti sudah diteruskan ke kategoris (references 'propagate')
        // sebelum nama kelompok disalin di bawah.
        parent::booted();

        static::saving(function (self $menu): void {
            $menu->menu = trim((string) $menu->menu);
        });

        static::updated(function (self $menu): void {
            if ($menu->wasChanged(['menu', 'url_menu'])) {
                Kategori::query()->where('url_kategori', $menu->url_menu)->update(['kategori' => $menu->menu]);
            }

            $lama = trim((string) $menu->getOriginal('url_menu'));
            $baru = trim((string) $menu->url_menu);
            if ($menu->wasChanged('url_menu') && $lama !== '' && $baru !== '') {
                Pengalihan::catat('/mesin/'.$lama, '/mesin/'.$baru, 'menu');

                // Halaman kategorinya ikut pindah (/mesin/{kelompok}/{kategori}); kategoris.url_kategori
                // sudah diteruskan ke slug baru oleh references 'propagate' di parent::booted().
                foreach (Kategori::query()->where('url_kategori', $baru)->pluck('url_submenu') as $slug) {
                    $dari = Kategori::alamat($lama, (string) $slug);
                    $ke = Kategori::alamat($baru, (string) $slug);
                    if ($dari && $ke) {
                        Pengalihan::catat('/'.$dari, '/'.$ke, 'menu');
                    }
                }
            }
        });
    }
}
