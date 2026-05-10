<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SezioneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sezione extends Model
{
    /** @use HasFactory<SezioneFactory> */
    use HasFactory;

    protected $table = 'sezioni';

    protected $fillable = [
        'codice',
        'nominativo',
        'regione',
        'provincia',
        'email',
        'pec',
        'sito_web',
        'telefono',
        'indirizzo',
        'iscritti_count',
        'presidente_nome',
        'anno_fondazione',
        'ente_terzo_settore',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ente_terzo_settore' => 'boolean',
            'iscritti_count' => 'integer',
            'anno_fondazione' => 'integer',
        ];
    }

    /** @return HasMany<Sottosezione, $this> */
    public function sottosezioni(): HasMany
    {
        return $this->hasMany(Sottosezione::class);
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

    /** @param Builder<Sezione> $query
     *  @return Builder<Sezione> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
