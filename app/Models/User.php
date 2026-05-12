<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Un user può avere sezione_id XOR sottosezione_id (o entrambi null per admin/gr_manager).
 * Questo invariante è mantenuto a livello applicativo (factory, policy, UI) e non via DB constraint.
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Impersonate;
    use LogsActivity;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'codice_cai',
        'sezione_id',
        'sottosezione_id',
        'contact_email',
        'email_is_fallback',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_is_fallback' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isAdmin(),
            'gr' => $this->isGrManager(),
            'sezione' => $this->isSezione(),
            default => false,
        };
    }

    /** Email effettiva per notifiche: contact_email se impostata, altrimenti email di login (§3.1). */
    public function getEffectiveContactEmailAttribute(): string
    {
        return $this->contact_email ?? $this->email;
    }

    public function routeNotificationForMail(): string
    {
        return $this->effective_contact_email;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'codice_cai', 'sezione_id', 'sottosezione_id', 'is_active', 'email_is_fallback'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    public function canBeImpersonated(): bool
    {
        return $this->is_active && ! $this->isAdmin();
    }

    /** @param Builder<User> $query
     *  @return Builder<User> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isGrManager(): bool
    {
        return $this->hasRole('gr_manager');
    }

    public function isSezione(): bool
    {
        return $this->hasRole('sezione');
    }

    /** @return BelongsTo<Sezione, $this> */
    public function sezione(): BelongsTo
    {
        return $this->belongsTo(Sezione::class);
    }

    /** @return BelongsTo<Sottosezione, $this> */
    public function sottosezione(): BelongsTo
    {
        return $this->belongsTo(Sottosezione::class);
    }

    /** @return HasMany<Prenotazione, $this> */
    public function prenotazioni(): HasMany
    {
        return $this->hasMany(Prenotazione::class);
    }

    /** @return HasMany<Prenotazione, $this> */
    public function prenotazioniApprovate(): HasMany
    {
        return $this->hasMany(Prenotazione::class, 'approvato_da');
    }
}
