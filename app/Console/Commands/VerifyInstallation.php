<?php

namespace App\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class VerifyInstallation extends Command
{
    protected $signature = 'heavypack:verify';

    protected $description = 'Check the HeavyPack admin runtime, database, schema mapping, and media.';

    public function handle(): int
    {
        $this->info('PHP '.PHP_VERSION.' · Laravel '.app()->version().' · Filament '.InstalledVersions::getPrettyVersion('filament/filament'));
        $this->line('Database internal admin: '.DB::connection()->getDatabaseName());
        $this->line('Database katalog: '.DB::connection('catalog')->getDatabaseName());
        $missing = [];
        $rows = [];
        foreach (config('legacy') as $key => $definition) {
            if (! Schema::connection('catalog')->hasTable($definition['table'])) {
                $missing[] = $definition['table'];

                continue;
            }
            $columns = Schema::connection('catalog')->getColumnListing($definition['table']);
            foreach ([$definition['pk'], ...array_column($definition['fields'], 'name')] as $column) {
                if (! in_array($column, $columns, true)) {
                    $missing[] = $definition['table'].'.'.$column;
                }
            }
            $rows[] = [$definition['label'], $definition['table'], DB::connection('catalog')->table($definition['table'])->count()];
        }
        $this->table(['Modul', 'Tabel', 'Jumlah data'], $rows);
        if ($missing) {
            $this->error('Missing schema: '.implode(', ', $missing));

            return self::FAILURE;
        }
        foreach (['produk-hv', 'customer-hv', 'article', 'images'] as $directory) {
            if (! is_dir(Storage::disk('legacy')->path($directory))) {
                $this->error('Missing media directory: '.$directory);

                return self::FAILURE;
            }
        }
        $this->info('All module schemas and media directories are ready.');

        return self::SUCCESS;
    }
}
