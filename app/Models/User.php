<?php

namespace App\Models;

use App\Support\CurrentCompanyTeamResolver;
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
use Spatie\Permission\PermissionRegistrar;
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

    /**
     * Whether this user holds the global (company_id = NULL) Super Admin role.
     *
     * With Spatie teams enabled, hasRole() only sees assignments for the
     * current company context. Super Admin is assigned globally, so the check
     * must run with a NULL team context.
     */
    protected ?bool $memoizedIsSuperAdmin = null;

    public function isSuperAdmin(): bool
    {
        if ($this->memoizedIsSuperAdmin !== null) {
            return $this->memoizedIsSuperAdmin;
        }

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(null);
        $this->unsetRelation('roles');

        try {
            return $this->memoizedIsSuperAdmin = $this->hasRole('Super Admin');
        } finally {
            CurrentCompanyTeamResolver::clearOverride();
            $this->unsetRelation('roles');
        }
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status === 'restricted') {
            return false;
        }

        if ($panel->getId() === 'superadmin') {
            return $this->isSuperAdmin();
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Allow access when email is verified, OR when the user has no email
        // at all (phone-only account) — email verification cannot apply.
        $emailOk = $this->hasVerifiedEmail() || blank($this->email);

        // The blog editorial panel is open to anyone holding blog.view in the
        // current company (Admins, Editors, Read Only).
        if ($panel->getId() === 'blog') {
            return $emailOk && $this->can('blog.view');
        }

        return $emailOk && $this->hasAnyRole([
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
