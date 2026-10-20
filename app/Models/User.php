<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Le rôle n'est pas « fillable » : il ne se change que par une action d'administration.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'member',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Le panneau de modération est réservé aux modérateurs et à l'administrateur.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isModerator() && $this->hasVerifiedEmail();
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [Role::Moderator, Role::Admin], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** @return HasMany<Card, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'author_id');
    }

    /** @return HasMany<Verdict, $this> */
    public function proposedVerdicts(): HasMany
    {
        return $this->hasMany(Verdict::class, 'proposed_by');
    }
}
