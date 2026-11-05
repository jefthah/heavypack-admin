<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu katalog = kelompok mesin di website (filter, label kartu beranda, halaman /product/{url_menu}).
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
            if ($menu->wasChanged('url_menu') && $lama !== '') {
                Pengalihan::catat('/product/'.$lama, '/product/'.$menu->url_menu, 'menu');
            }
        });
    }
}
