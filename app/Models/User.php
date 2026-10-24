<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'active',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'web';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function isCompanyOwner(): bool
    {
        return $this->hasRole('Company Owner', 'web');
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function canManageCompany(): bool
    {
        return $this->hasAnyRole([
            'Company Owner',
            'Admin',
        ], 'web');
    }

    public function canManageStaff(): bool
    {
        return $this->hasPermissionTo('manage staff', 'web');
    }
}
