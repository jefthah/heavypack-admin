<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ReadsLegacyAdmins;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

#[Signature('heavypack:import-legacy-admins
    {--dry-run : Show what would be imported without saving anything}
    {--include-no-access : Also import levels that could not log in to the old panel, as inactive editors}
    {--active : With --include-no-access, make those editors active right away}
    {--connection= : Database connection that can read the legacy admins table}')]
#[Description('Import old bawaanAdmin accounts into admin_users with roles and temporary passwords.')]
class ImportLegacyAdmins extends Command
{
    use ReadsLegacyAdmins;

    /**
     * Levels that could log in to the old panel and the role they receive in the new panel.
     *
     * @var array<int, string>
     */
    private const ROLE_BY_LEVEL = [1 => 'super_admin', 2 => 'editor', 3 => 'editor', 4 => 'editor'];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('This command is restricted to the local XAMPP environment.');
        }

        $isDryRun = (bool) $this->option('dry-run');
        $legacyAdmins = DB::connection($this->legacyAdminConnection())->table('admins')->orderBy('level')->orderBy('nama')->get();
        $existingEmails = User::query()->pluck('email')->map(fn (string $email): string => Str::lower($email))->all();
        $rows = [];
        $accounts = [];

        foreach ($legacyAdmins as $legacy) {
            $email = Str::lower(trim((string) $legacy->email));
            $level = (int) $legacy->level;
            $hasOldAccess = array_key_exists($level, self::ROLE_BY_LEVEL);
            $role = self::ROLE_BY_LEVEL[$level] ?? 'editor';
            $isActive = $hasOldAccess || (bool) $this->option('active');

            $skipReason = match (true) {
                ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'Dilewati: email tidak valid',
                in_array($email, $existingEmails, true) => 'Dilewati: email sudah terdaftar',
                ! $hasOldAccess && ! $this->option('include-no-access') => 'Dilewati: tidak punya akses panel lama',
                default => null,
            };

            if ($skipReason === null) {
                $existingEmails[] = $email;
                $accounts[] = [
                    'name' => trim((string) $legacy->nama) ?: (string) $legacy->username,
                    'email' => $email,
                    'role' => $role,
                    'is_active' => $isActive,
                    'password' => $isDryRun ? null : Str::password(16, symbols: false),
                    'username' => (string) $legacy->username,
                ];
            }

            $rows[] = [
                $legacy->nama,
                $this->maskEmail($email),
                $level,
                $legacy->jabatan,
                $role.($isActive ? '' : ' (nonaktif)'),
                $skipReason ?? ($isDryRun ? 'Akan dibuat' : 'Dibuat'),
            ];
        }

        $this->table(['Nama', 'Email', 'Level', 'Jabatan lama', 'Role baru', 'Hasil'], $rows);

        if ($isDryRun) {
            $this->info('Mode uji coba: '.count($accounts).' akun akan dibuat. Tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        if ($accounts === []) {
            $this->info('Tidak ada akun baru yang perlu dibuat.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                User::create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $account['password'],
                    'role' => $account['role'],
                    'is_active' => $account['is_active'],
                ]);
            }
        });

        $path = 'legacy-admin-import-'.now()->format('Ymd-His').'.csv';
        Storage::disk('local')->put($path, $this->toCsv($accounts));

        $this->info(count($accounts).' akun dibuat. Password sementara tersimpan di: '.Storage::disk('local')->path($path));
        $this->warn('Bagikan password ke masing-masing pemilik akun, minta mereka menggantinya lewat menu profil, lalu hapus file tersebut.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{name: string, email: string, role: string, is_active: bool, password: ?string, username: string}>  $accounts
     */
    private function toCsv(array $accounts): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['nama', 'email', 'role', 'aktif', 'password_sementara', 'username_lama'], ',', '"', '');
        foreach ($accounts as $account) {
            fputcsv($handle, [
                $account['name'], $account['email'], $account['role'],
                $account['is_active'] ? 'ya' : 'tidak', $account['password'], $account['username'],
            ], ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
