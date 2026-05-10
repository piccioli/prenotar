<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SottosezioneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sottosezione extends Model
{
    /** @use HasFactory<SottosezioneFactory> */
    use HasFactory;

    protected $table = 'sottosezioni';

    protected $fillable = [
        'codice',
        'nominativo',
        'sezione_id',
        'codice_sezione',
        'regione',
        'provincia',
        'email',
        'indirizzo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Etichetta con prefisso S.SEZ. e riferimento alla sezione padre (risolve BUG-05). */
    public function getLabelAttribute(): string
    {
        return 'S.SEZ. '.$this->nominativo.' (sez. rif. '.$this->sezione?->nominativo.')';
    }

    /** @return BelongsTo<Sezione, $this> */
    public function sezione(): BelongsTo
    {
        return $this->belongsTo(Sezione::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Prenotazione, $this> */
    public function prenotazioni(): HasMany
    {
        return $this->hasMany(Prenotazione::class);
    }

    /** @param Builder<Sottosezione> $query
     *  @return Builder<Sottosezione> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
