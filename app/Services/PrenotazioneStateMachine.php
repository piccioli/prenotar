<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneInviata;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\User;
use App\Rules\NoOverlapTorre;
use App\Rules\UnicaPrenotazioneAttivaPerUser;
use App\Settings\GrSettings;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class PrenotazioneStateMachine
{
    public function __construct(private readonly GrSettings $grSettings) {}

    public function inviaRichiesta(Prenotazione $p, User $u): void
    {
        if ($p->status !== PrenotazioneStatus::Bozza) {
            throw new DomainException('Solo le prenotazioni in bozza possono essere inviate.');
        }

        if (! $p->hasMedia('delibera_consiglio')) {
            throw new DomainException('La delibera del consiglio direttivo è obbligatoria per inviare la richiesta.');
        }

        $giorni = $this->grSettings->giorni_minimi_caricamento_documenti;
        if ($p->data_inizio_prenotazione->lt(today()->addDays($giorni))) {
            throw new DomainException("La prenotazione deve iniziare almeno {$giorni} giorni da oggi.");
        }

        Validator::make(
            ['torre_id' => $p->torre_id, 'user_id' => $u->id],
            [
                'torre_id' => [
                    new NoOverlapTorre(
                        torreId: $p->torre_id,
                        dataInizio: $p->data_inizio_prenotazione->toDateString(),
                        dataFine: $p->data_fine_prenotazione->toDateString(),
                        excludePrenotazioneId: $p->id,
                    ),
                ],
                'user_id' => [
                    new UnicaPrenotazioneAttivaPerUser($u, $p->id),
                ],
            ]
        )->validate();

        DB::transaction(function () use ($p, $u): void {
            $p->update(['status' => PrenotazioneStatus::Inviata]);

            PrenotazioneHistory::create([
                'prenotazione_id' => $p->id,
                'user_id' => $u->id,
                'status_from' => PrenotazioneStatus::Bozza,
                'status_to' => PrenotazioneStatus::Inviata,
                'note' => 'Invio richiesta da sezione.',
                'created_at' => now(),
            ]);
        });

        event(new PrenotazioneInviata($p->fresh()));
    }

    public function approva(Prenotazione $p, User $u, ?int $torreId = null): never
    {
        throw new \BadMethodCallException('Implementazione Fase 4.');
    }

    public function rifiuta(Prenotazione $p, User $u, string $motivo): never
    {
        throw new \BadMethodCallException('Implementazione Fase 4.');
    }

    public function changeDates(Prenotazione $p, User $u, string $dataInizio, string $dataFine): never
    {
        throw new \BadMethodCallException('Implementazione Fase 4.');
    }

    public function reassignTorre(Prenotazione $p, User $u, int $torreId): never
    {
        throw new \BadMethodCallException('Implementazione Fase 4.');
    }

    public function caricaPdfFirmato(Prenotazione $p, User $u, string $path): never
    {
        throw new \BadMethodCallException('Implementazione Fase 4.');
    }

    public function inviaAssicurazione(Prenotazione $p, User $u): never
    {
        throw new \BadMethodCallException('Implementazione Fase 5.');
    }

    public function concludi(Prenotazione $p): never
    {
        throw new \BadMethodCallException('Implementazione Fase 7.');
    }
}
