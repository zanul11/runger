<?php

namespace App\Models;

use App\Mail\RunnerPasswordReset;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class Runner extends Authenticatable
{
    use Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'birthdate' => 'date',
        'password' => 'hashed',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(GtrRegistration::class);
    }

    /**
     * Dipanggil oleh password broker 'runners' (lupa password & kirim link dari admin).
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new RunnerPasswordReset($this, $token));
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
