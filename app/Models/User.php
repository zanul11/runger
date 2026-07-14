<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MARSHAL = 'marshal';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMarshal(): bool
    {
        return $this->role === self::ROLE_MARSHAL;
    }

    /** Filament admin panel hanya untuk role admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /** Penugasan pos timing untuk marshal (lihat App\Models\GtrTimingPointAssignment). */
    public function timingPointAssignments(): HasMany
    {
        return $this->hasMany(GtrTimingPointAssignment::class);
    }

    /** Penugasan aktif marshal (maks. satu per event). */
    public function activeAssignment(?int $eventId = null): ?GtrTimingPointAssignment
    {
        return $this->timingPointAssignments()
            ->where('is_active', true)
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->latest('assigned_at')
            ->first();
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
        ];
    }
}
