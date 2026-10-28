<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, ['super_admin', 'editor'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_active && $this->role === 'super_admin';
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->exists && $user->getOriginal('role') === 'super_admin' && $user->getOriginal('is_active')
                && ($user->role !== 'super_admin' || ! $user->is_active)
                && ! static::whereKeyNot($user->getKey())->where('role', 'super_admin')->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['role' => 'Minimal satu super admin aktif harus tersedia.']);
            }
        });
        static::deleting(function (self $user): void {
            if (auth()->id() === $user->id || ($user->isSuperAdmin()
                && ! static::whereKeyNot($user->id)->where('role', 'super_admin')->where('is_active', true)->exists())) {
                throw ValidationException::withMessages(['record' => 'Akun sendiri atau super admin aktif terakhir tidak dapat dihapus.']);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
