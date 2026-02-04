<?php

namespace App\Models;

use App\Enums\BookloreConnectionStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
            'booklore_library_id' => 'integer',
            'booklore_path_id' => 'integer',
            'booklore_access_token' => 'encrypted:string',
            'booklore_refresh_token' => 'encrypted:string',
            'booklore_access_token_expires_at' => 'datetime',
            'booklore_retention_hours' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getBookloreStatus(): BookloreConnectionStatus
    {
        if (! $this->booklore_refresh_token || ! $this->booklore_access_token) {
            return BookloreConnectionStatus::NotConnected;
        }
        if (! $this->booklore_library_id || ! $this->booklore_path_id) {
            return BookloreConnectionStatus::IncompleteSetup;
        }

        return BookloreConnectionStatus::Connected;
    }

    public function finishedBookloreSetup(): bool
    {
        return $this->getBookloreStatus() === BookloreConnectionStatus::Connected;
    }
}
