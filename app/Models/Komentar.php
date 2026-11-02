<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Komentar extends LegacyRecord
{
    protected static string $moduleKey = 'komentar';

    /** Artikel tempat komentar ini tampil di website. */
    public function artikel(): BelongsTo
    {
        return $this->belongsTo(Artikel::class, 'id_article', 'id');
    }
}
