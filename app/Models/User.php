<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Enums\UserRole;
use Modules\Core\Models\Company;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(\Modules\Expenses\Models\Expense::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('is_owner')
            ->withTimestamps();
    }

    public function getCurrentCompanyId(): ?int
    {
        return session('current_company_id') ?? $this->companies()->first()?->id;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN->value);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole(UserRole::SUPER_ADMIN->value)
            || $this->hasRole(UserRole::ADMIN->value)
            || $this->hasRole(UserRole::ASSIST->value)) {
            return true;
        }

        if ('company' === $panel->getId()) {
            return $this->hasRole(UserRole::CUSTOMER_ADMIN->value)
                || $this->hasRole(UserRole::CUSTOMER->value);
        }

        return false;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->companies()->whereKey($tenant->getKey())->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->companies;
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
