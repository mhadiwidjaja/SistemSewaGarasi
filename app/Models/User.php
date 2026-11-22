<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * An application user: either the admin or a renter.
 *
 * Renters register themselves and can only log in after the admin approves them.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * Attributes a form may fill. Role and status are left out on purpose: they are only set by the
     * application itself, so a renter can never make themselves admin or approve themselves.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nik',
        'name',
        'phone',
        'email',
        'password',
    ];

    /** @var list<string> */
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => RegistrationStatus::class,
        ];
    }

    /**
     * Get the rentals made by this renter.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }

    /**
     * Determine whether the user is the admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine whether the user's registration has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === RegistrationStatus::Approved;
    }

    /**
     * Determine whether the user is a renter still waiting for verification.
     */
    public function isPendingRenter(): bool
    {
        return $this->role === UserRole::Renter && $this->status === RegistrationStatus::Pending;
    }

    /**
     * Limit the query to renters still waiting for verification.
     *
     * @param  Builder<User>  $query
     */
    public function scopePendingRenters(Builder $query): void
    {
        $query->where('role', UserRole::Renter)->where('status', RegistrationStatus::Pending);
    }
}
