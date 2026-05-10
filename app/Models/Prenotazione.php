<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use Database\Factories\PrenotazioneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property PrenotazioneStatus $status
 * @property ResponsabileTipo $responsabile_tipo
 */
class Prenotazione extends Model implements HasMedia
{
    /** @use HasFactory<PrenotazioneFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'prenotazioni';

    protected $fillable = [
        'user_id',
        'sezione_id',
        'sottosezione_id',
        'torre_id',
        'nome_evento',
        'tipo_evento',
        'descrizione_evento',
        'indirizzo_evento',
        'data_inizio_evento',
        'data_fine_evento',
        'data_inizio_prenotazione',
        'data_fine_prenotazione',
        'data_ritiro',
        'luogo_ritiro',
        'data_riconsegna',
        'luogo_riconsegna',
        'azienda_trasporto',
        'targa_autoveicolo',
        'responsabile_nome',
        'responsabile_titolo_cai',
        'responsabile_codice_cai',
        'responsabile_telefono',
        'responsabile_email',
        'responsabile_tipo',
        'status',
        'approvato_da',
        'approvato_at',
        'motivo_rifiuto',
        'pdf_firmato_at',
        'pdf_firmato_path',
        'inviato_assicurazione_at',
        'concluso_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PrenotazioneStatus::class,
            'responsabile_tipo' => ResponsabileTipo::class,
            'data_inizio_evento' => 'date',
            'data_fine_evento' => 'date',
            'data_inizio_prenotazione' => 'date',
            'data_fine_prenotazione' => 'date',
            'data_ritiro' => 'date',
            'data_riconsegna' => 'date',
            'approvato_at' => 'datetime',
            'pdf_firmato_at' => 'datetime',
            'inviato_assicurazione_at' => 'datetime',
            'concluso_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        $this->addMediaCollection('delibera_consiglio')
            ->singleFile()
            ->acceptsMimeTypes($allowedMimeTypes)
            ->useDisk('local');

        $this->addMediaCollection('autorizzazione_suolo_pubblico')
            ->singleFile()
            ->acceptsMimeTypes($allowedMimeTypes)
            ->useDisk('local');

        $this->addMediaCollection('autorizzazione_ztl')
            ->singleFile()
            ->acceptsMimeTypes($allowedMimeTypes)
            ->useDisk('local');

        $this->addMediaCollection('patente_responsabile')
            ->singleFile()
            ->acceptsMimeTypes($allowedMimeTypes)
            ->useDisk('local');

        $this->addMediaCollection('altri')
            ->acceptsMimeTypes($allowedMimeTypes)
            ->useDisk('local');
    }

    /** Etichetta del proprietario (risolve BUG-05: label distintiva sezione/sottosezione). */
    public function getProprietarioLabelAttribute(): string
    {
        $sottosezione = $this->sottosezione;
        if ($sottosezione !== null) {
            return 'S.SEZ. '.$sottosezione->nominativo.' (sez. rif. '.$sottosezione->sezione?->nominativo.')';
        }

        return 'SEZ. '.$this->sezione?->nominativo;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return BelongsTo<Torre, $this> */
    public function torre(): BelongsTo
    {
        return $this->belongsTo(Torre::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvatoDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approvato_da');
    }

    /** @return HasMany<PrenotazioneHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(PrenotazioneHistory::class);
    }

    /** @param Builder<Prenotazione> $query
     *  @return Builder<Prenotazione> */
    public function scopeAttive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            PrenotazioneStatus::Concluso->value,
            PrenotazioneStatus::Annullata->value,
        ]);
    }

    /** @param Builder<Prenotazione> $query
     *  @return Builder<Prenotazione> */
    public function scopeArchiviate(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PrenotazioneStatus::Concluso->value,
            PrenotazioneStatus::Annullata->value,
        ]);
    }

    /** @param Builder<Prenotazione> $query
     *  @return Builder<Prenotazione> */
    public function scopeProprietarioOf(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
