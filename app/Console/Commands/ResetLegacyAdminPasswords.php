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

#[Signature('heavypack:reset-legacy-admin-passwords
    {password=password : New password for every account that came from the old bawaanAdmin panel}
    {--dry-run : Show which accounts would change without saving anything}
    {--connection= : Database connection that can read the legacy admins table}')]
#[Description('Set one shared password for the accounts imported from the old bawaanAdmin panel.')]
class ResetLegacyAdminPasswords extends Command
{
    use ReadsLegacyAdmins;

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('This command is restricted to the local XAMPP environment.');
        }

        $password = (string) $this->argument('password');

        if (blank($password)) {
            $this->error('Password tidak boleh kosong.');

            return self::FAILURE;
        }

        $legacyEmails = DB::connection($this->legacyAdminConnection())->table('admins')->pluck('email')
            ->map(fn (?string $email): string => Str::lower(trim((string) $email)))
            ->filter()
            ->all();
        $accounts = User::query()->whereIn('email', $legacyEmails)->orderBy('name')->get();

        if ($accounts->isEmpty()) {
            $this->info('Tidak ada akun dari admin lama di admin baru.');

            return self::SUCCESS;
        }

        $this->table(['Nama', 'Email', 'Role', 'Status'], $accounts->map(fn (User $account): array => [
            $account->name,
            $this->maskEmail($account->email),
            $account->role,
            $account->is_active ? 'aktif' : 'nonaktif',
        ])->all());

        if ($this->option('dry-run')) {
            $this->info('Mode uji coba: password '.$accounts->count().' akun akan diganti. Tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Ganti password '.$accounts->count().' akun di atas?', true)) {
            $this->info('Dibatalkan. Tidak ada yang diubah.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($accounts, $password): void {
            foreach ($accounts as $account) {
                $account->update(['password' => $password]);
            }
        });

        $this->info('Password '.$accounts->count().' akun sudah diganti.');
        $this->warn('Password ini sama untuk semua akun dan mudah ditebak. Minta setiap orang menggantinya lewat menu profil setelah login.');

        $this->deleteOutdatedImportFiles();

        return self::SUCCESS;
    }

    /**
     * Temporary passwords from earlier imports no longer work after the reset.
     */
    private function deleteOutdatedImportFiles(): void
    {
        $files = collect(Storage::disk('local')->files())
            ->filter(fn (string $file): bool => Str::is('legacy-admin-import-*.csv', $file))
            ->values()
            ->all();

        if ($files === []) {
            return;
        }

        if ($this->confirm('Password di '.count($files).' file CSV impor lama sudah tidak berlaku. Hapus file tersebut?', true)) {
            Storage::disk('local')->delete($files);
            $this->info('File CSV impor lama sudah dihapus.');
        }
    }
}
