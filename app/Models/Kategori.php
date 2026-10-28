<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends LegacyRecord
{
    protected static string $moduleKey = 'kategori';

    public function products(): HasMany
    {
        return $this->hasMany(Produk::class, 'id_kategori', 'id_kategoris');
    }
}
