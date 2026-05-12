<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TorreFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Torre extends Model
{
    /** @use HasFactory<TorreFactory> */
    use HasFactory;

    use LogsActivity;

    protected $table = 'torri';

    protected $fillable = [
        'nome',
        'descrizione',
        'indirizzo_deposito',
        'foto_path',
        'specs_tecniche_pdf_path',
        'manuale_pdf_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('torre');
    }

    /** @return HasMany<Prenotazione, $this> */
    public function prenotazioni(): HasMany
    {
        return $this->hasMany(Prenotazione::class);
    }

    /** @param Builder<Torre> $query
     *  @return Builder<Torre> */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
