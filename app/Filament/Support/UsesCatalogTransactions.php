<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Support\Facades\DB;

trait UsesCatalogTransactions
{
    protected function beginDatabaseTransaction(): void
    {
        if ($this->hasDatabaseTransactions()) {
            DB::connection('catalog')->beginTransaction();
        }
    }

    protected function commitDatabaseTransaction(): void
    {
        if ($this->hasDatabaseTransactions()) {
            DB::connection('catalog')->commit();
        }
    }

    protected function rollBackDatabaseTransaction(): void
    {
        if ($this->hasDatabaseTransactions()) {
            DB::connection('catalog')->rollBack();
        }
    }

    protected function wrapInDatabaseTransaction(Closure $callback): mixed
    {
        return $this->hasDatabaseTransactions() ? DB::connection('catalog')->transaction($callback) : $callback();
    }
}
