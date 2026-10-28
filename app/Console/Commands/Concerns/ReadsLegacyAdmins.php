<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Str;

trait ReadsLegacyAdmins
{
    /**
     * The admin and content database accounts cannot read the legacy admins table,
     * so the local XAMPP root account is used, like the other setup commands.
     */
    private function legacyAdminConnection(): string
    {
        if (filled($connection = $this->option('connection'))) {
            return $connection;
        }

        config(['database.connections.legacy_admins' => [
            ...config('database.connections.catalog'),
            'username' => 'root',
            'password' => '',
        ]]);

        return 'legacy_admins';
    }

    private function maskEmail(string $email): string
    {
        $at = strpos($email, '@');

        return $at === false ? $email : Str::mask($email, '*', min(2, $at), max($at - 2, 0));
    }
}
