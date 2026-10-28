<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

abstract class LegacyRecord extends Model
{
    protected static string $moduleKey;

    protected $guarded = [];

    protected $connection = 'catalog';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        $definition = static::definition();
        $this->table = $definition['table'];
        $this->primaryKey = $definition['pk'];
        foreach ($definition['fields'] as $field) {
            $this->attributes[$field['name']] = in_array($field['type'], ['number', 'category'], true) ? 0 : '';
        }
        parent::__construct($attributes);
    }

    public static function definition(): array
    {
        return config('legacy.'.static::$moduleKey);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategoris');
    }

    public function hasDependentRecords(): bool
    {
        foreach (static::definition()['references'] ?? [] as $reference) {
            if (Schema::connection('catalog')->hasTable($reference['table']) && Schema::connection('catalog')->hasColumn($reference['table'], $reference['column'])) {
                $value = isset($reference['value']) ? $this->getAttribute($reference['value']) : $this->getKey();
                if (DB::connection('catalog')->table($reference['table'])->where($reference['column'], $value)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            foreach (static::definition()['fields'] as $field) {
                if ($record->getAttribute($field['name']) === null) {
                    $record->setAttribute($field['name'], in_array($field['type'], ['number', 'category'], true) ? 0 : '');
                }
            }
            if ($record->exists && Schema::connection('catalog')->hasColumn($record->getTable(), 'updated_at')) {
                $record->setAttribute('updated_at', now());
            }
        });
        static::deleting(function (self $record): void {
            if ($record->hasDependentRecords()) {
                throw ValidationException::withMessages(['record' => 'Data masih dipakai oleh konten lain. Pindahkan konten tersebut sebelum menghapus.']);
            }
        });
        static::updated(function (self $record): void {
            foreach (static::definition()['references'] ?? [] as $reference) {
                $field = $reference['value'] ?? null;
                if (($reference['propagate'] ?? false) && $field && $record->wasChanged($field)) {
                    DB::connection('catalog')->table($reference['table'])->where($reference['column'], $record->getOriginal($field))
                        ->update([$reference['column'] => $record->getAttribute($field)]);
                }
            }
        });
    }
}
