<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneApprovata;
use App\Events\PrenotazioneInviata;
use App\Events\PrenotazioneRifiutata;
use App\Events\PrenotazioneTorreRiassegnata;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\User;
use App\Rules\NoOverlapTorre;
use App\Rules\UnicaPrenotazioneAttivaPerUser;
use App\Settings\GrSettings;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

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

    public function approva(Prenotazione $p, User $u, ?int $torreId = null): void
    {
        if ($p->status !== PrenotazioneStatus::Inviata) {
            throw new DomainException('Solo le prenotazioni in stato Inviata possono essere approvate.');
        }

        if ($torreId !== null && $torreId !== $p->torre_id) {
            Validator::make(
                ['torre_id' => $torreId],
                [
                    'torre_id' => [
                        new NoOverlapTorre(
                            torreId: $torreId,
                            dataInizio: $p->data_inizio_prenotazione->toDateString(),
                            dataFine: $p->data_fine_prenotazione->toDateString(),
                            excludePrenotazioneId: $p->id,
                        ),
                    ],
                ]
            )->validate();
        }

        $note = 'Approvata da GR.';

        DB::transaction(function () use ($p, $u, $torreId, &$note): void {
            $data = [
                'status' => PrenotazioneStatus::Approvata,
                'approvato_da' => $u->id,
                'approvato_at' => now(),
            ];

            if ($torreId !== null && $torreId !== $p->torre_id) {
                $data['torre_id'] = $torreId;
                $note = 'Approvata da GR (torre riassegnata).';
            }

            $p->update($data);

            PrenotazioneHistory::create([
                'prenotazione_id' => $p->id,
                'user_id' => $u->id,
                'status_from' => PrenotazioneStatus::Inviata,
                'status_to' => PrenotazioneStatus::Approvata,
                'note' => $note,
                'created_at' => now(),
            ]);
        });

        event(new PrenotazioneApprovata($p->fresh()));
    }

    public function rifiuta(Prenotazione $p, User $u, string $motivo): void
    {
        if ($p->status !== PrenotazioneStatus::Inviata) {
            throw new DomainException('Solo le prenotazioni in stato Inviata possono essere rifiutate.');
        }

        if (trim($motivo) === '') {
            throw new InvalidArgumentException('Il motivo di rifiuto è obbligatorio.');
        }

        DB::transaction(function () use ($p, $u, $motivo): void {
            $p->update([
                'status' => PrenotazioneStatus::Annullata,
                'motivo_rifiuto' => $motivo,
                'archived_at' => now(),
            ]);

            PrenotazioneHistory::create([
                'prenotazione_id' => $p->id,
                'user_id' => $u->id,
                'status_from' => PrenotazioneStatus::Inviata,
                'status_to' => PrenotazioneStatus::Annullata,
                'note' => "Rifiutata: {$motivo}",
                'created_at' => now(),
            ]);
        });

        event(new PrenotazioneRifiutata($p->fresh(), $motivo));
    }

    public function changeDates(Prenotazione $p, User $u, string $dataInizio, string $dataFine): never
    {
        throw new \BadMethodCallException('Implementazione Fase 5.');
    }

    public function reassignTorre(Prenotazione $p, User $u, int $torreId): void
    {
        $statiConsentiti = [PrenotazioneStatus::Approvata, PrenotazioneStatus::InviatoPdfFirmato];

        if (! in_array($p->status, $statiConsentiti, strict: true)) {
            throw new DomainException(
                'La riassegnazione torre è consentita solo su prenotazioni Approvate o con PDF firmato. '.
                'Per prenotazioni Inviate usare approva() con il parametro torreId.'
            );
        }

        if ($torreId === $p->torre_id) {
            throw new DomainException('La torre selezionata è già assegnata a questa prenotazione.');
        }

        Validator::make(
            ['torre_id' => $torreId],
            [
                'torre_id' => [
                    new NoOverlapTorre(
                        torreId: $torreId,
                        dataInizio: $p->data_inizio_prenotazione->toDateString(),
                        dataFine: $p->data_fine_prenotazione->toDateString(),
                        excludePrenotazioneId: $p->id,
                    ),
                ],
            ]
        )->validate();

        $oldTorreId = $p->torre_id;

        DB::transaction(function () use ($p, $u, $torreId, $oldTorreId): void {
            $p->update(['torre_id' => $torreId]);

            PrenotazioneHistory::create([
                'prenotazione_id' => $p->id,
                'user_id' => $u->id,
                'status_from' => $p->status,
                'status_to' => $p->status,
                'note' => "Torre riassegnata da #{$oldTorreId} a #{$torreId}.",
                'created_at' => now(),
            ]);
        });

        event(new PrenotazioneTorreRiassegnata($p->fresh(), $oldTorreId ?? 0));
    }

    public function caricaPdfFirmato(Prenotazione $p, User $u, string $path): never
    {
        throw new \BadMethodCallException('Implementazione Fase 5.');
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
