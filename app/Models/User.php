<?php

namespace App\Models;

use App\Notifications\PasswordResetToken;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Does NOT implement the MustVerifyEmail contract: that would activate
 * Laravel's automatic Registered-event listener, which sends the
 * framework's default verification email via a signed *web* route this
 * API-only app doesn't register. Email verification is handled entirely
 * by AuthService's OTP flow instead (see AuthController::verifyEmail).
 * The hasVerifiedEmail()/email_verified_at semantics still work via the
 * MustVerifyEmail trait pulled in by the parent Authenticatable class.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_TENANT = 'tenant';

    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'terms_accepted_at',
        'privacy_accepted_at',
        'google_id',
        'avatar_url',
        'profile_completed',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
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
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'password' => 'hashed',
            'profile_completed' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function tenantProfile(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetToken($token));
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isTenant(): bool
    {
        return $this->role === self::ROLE_TENANT;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Compte créé uniquement via Google Sign-In, sans mot de passe local.
     */
    public function isGoogleOnly(): bool
    {
        return $this->password === null;
    }
}
