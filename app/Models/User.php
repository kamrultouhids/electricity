<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Available user roles / types (stored value => display label).
     */
    public const USER_TYPES = [
        'admin'            => 'Admin',
        'manager'          => 'Manager',
        'operator' => 'Operator',
        'reader'     => 'Reader',
        'collector'   => 'Collector',
    ];

    /**
     * Ability => roles allowed. Admin & manager can do everything; the rest
     * are scoped per the business rules.
     */
    public const ABILITIES = [
        'manage-customers'      => ['admin', 'manager', 'operator'],
        'access-meter-readings' => ['admin', 'manager', 'operator', 'reader'],
        'generate-bills'        => ['admin', 'manager', 'operator'],
        'revise-bills'          => ['admin', 'manager'],
        'collect-payments'      => ['admin', 'manager', 'operator', 'collector'],
        'view-due-list'         => ['admin', 'manager', 'operator', 'collector'],
        'view-reports'          => ['admin', 'manager', 'operator'],
        'manage-expenses'       => ['admin', 'manager'],
        'rate-settings'         => ['admin'],
        'manage-users'          => ['admin'],
        'view-bills'            => ['admin', 'manager', 'operator', 'collector'],
        'view-payments'         => ['admin', 'manager', 'operator', 'collector'],
    ];

    /**
     * Whether this user's role is allowed the given ability.
     */
    public function hasAbility(string $ability): bool
    {
        return in_array($this->user_type, self::ABILITIES[$ability] ?? [], true);
    }

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * Human-readable label for the stored user type.
     */
    public function getUserTypeLabelAttribute(): string
    {
        return self::USER_TYPES[$this->user_type] ?? $this->user_type;
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'status',
        'created_by',
        'updated_by',
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
}
