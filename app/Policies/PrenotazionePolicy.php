<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Models\User;

class PrenotazionePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('prenotazioni:view-any');
    }

    public function view(User $user, Prenotazione $prenotazione): bool
    {
        if (! $user->hasPermissionTo('prenotazioni:view')) {
            return false;
        }

        if ($user->isAdmin() || $user->isGrManager()) {
            return true;
        }

        return $user->id === $prenotazione->user_id;
    }

    /** Solo ruolo sezione; GR e admin non creano prenotazioni (§3 PIANO). */
    public function create(User $user): bool
    {
        return $user->isSezione() && $user->hasPermissionTo('prenotazioni:create');
    }

    /** Sezione proprietaria, solo se in BOZZA. */
    public function update(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isSezione()
            && $user->hasPermissionTo('prenotazioni:update')
            && $user->id === $prenotazione->user_id
            && $prenotazione->status === PrenotazioneStatus::Bozza;
    }

    /** Sezione proprietaria, solo se in BOZZA. */
    public function delete(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isSezione()
            && $user->hasPermissionTo('prenotazioni:delete')
            && $user->id === $prenotazione->user_id
            && $prenotazione->status === PrenotazioneStatus::Bozza;
    }

    public function approve(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:approve');
    }

    public function reject(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:reject');
    }

    public function changeDates(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:change-dates');
    }

    public function reassignTorre(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:reassign-torre');
    }

    public function generatePdfRichiesta(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:generate-pdf-richiesta');
    }

    public function generatePdfModulo3(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:generate-pdf-modulo3');
    }

    public function sendInsurance(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:send-insurance');
    }

    public function markConcluso(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isGrManager() && $user->hasPermissionTo('prenotazioni:mark-concluso');
    }

    /** Solo admin, per interventi straordinari (§5.3). */
    public function forceState(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('prenotazioni:force-state');
    }

    /** Solo admin, per prenotazioni di test/spam/duplicati (§5.3). */
    public function hardDelete(User $user, Prenotazione $prenotazione): bool
    {
        return $user->isAdmin() && $user->hasPermissionTo('prenotazioni:hard-delete');
    }
}
