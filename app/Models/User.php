<?php

namespace App\Models;

use App\Support\DemoGuard;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'department', 'preferences', 'status', 'last_login_at', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status === 'restricted') {
            return false;
        }

        if ($panel->getId() === 'superadmin') {
            return $this->hasRole('Super Admin');
        }

        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->hasVerifiedEmail() && $this->hasAnyRole([
            'Admin',
            'Finance',
            'Project Manager',
            'Staff',
            'Read Only',
        ]);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $isDemoAdmin = strcasecmp($user->email, 'admin@demo.erp') === 0
                && $user->companies()->where('is_demo', true)->exists();

            DemoGuard::ensureDemoAdminDeletionAllowed($isDemoAdmin);
        });
    }
}
