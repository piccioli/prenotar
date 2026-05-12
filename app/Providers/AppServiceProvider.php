<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\PrenotazioneApprovata;
use App\Events\PrenotazioneDateModificate;
use App\Events\PrenotazioneInviata;
use App\Events\PrenotazioneInviataAssicurazione;
use App\Events\PrenotazionePdfFirmatoCaricato;
use App\Events\PrenotazioneRifiutata;
use App\Events\PrenotazioneTorreRiassegnata;
use App\Events\UserSetPasswordRequested;
use App\Listeners\SendModulo3ToAssicurazione;
use App\Listeners\SendPrenotazioneApprovataNotification;
use App\Listeners\SendPrenotazioneDateModificateNotification;
use App\Listeners\SendPrenotazioneInviataNotification;
use App\Listeners\SendPrenotazionePdfFirmatoCaricatoNotification;
use App\Listeners\SendPrenotazioneRifiutataNotification;
use App\Listeners\SendPrenotazioneTorreRiassegnataNotification;
use App\Listeners\SendSetPasswordNotification;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\Torre;
use App\Models\User;
use App\Policies\PrenotazionePolicy;
use App\Policies\SezionePolicy;
use App\Policies\SottosezionePolicy;
use App\Policies\TorrePolicy;
use App\Policies\UserPolicy;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Prenotazione::class, PrenotazionePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Torre::class, TorrePolicy::class);
        Gate::policy(Sezione::class, SezionePolicy::class);
        Gate::policy(Sottosezione::class, SottosezionePolicy::class);

        Event::listen(UserSetPasswordRequested::class, SendSetPasswordNotification::class);
        Event::listen(PrenotazioneInviata::class, SendPrenotazioneInviataNotification::class);
        Event::listen(PrenotazioneApprovata::class, SendPrenotazioneApprovataNotification::class);
        Event::listen(PrenotazioneRifiutata::class, SendPrenotazioneRifiutataNotification::class);
        Event::listen(PrenotazioneTorreRiassegnata::class, SendPrenotazioneTorreRiassegnataNotification::class);
        Event::listen(PrenotazioneDateModificate::class, SendPrenotazioneDateModificateNotification::class);
        Event::listen(PrenotazionePdfFirmatoCaricato::class, SendPrenotazionePdfFirmatoCaricatoNotification::class);
        Event::listen(PrenotazioneInviataAssicurazione::class, SendModulo3ToAssicurazione::class);

        Event::listen(TakeImpersonation::class, function (TakeImpersonation $event): void {
            app(AuditLogger::class)->logAdminAction(
                'user.impersonate_begin',
                $event->impersonated,
                'Impersonate avviato da admin',
                [
                    'admin_id' => $event->impersonator->getAuthIdentifier(),
                    'target_id' => $event->impersonated->getAuthIdentifier(),
                ],
            );
        });

        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event): void {
            app(AuditLogger::class)->logAdminAction(
                'user.impersonate_end',
                $event->impersonated,
                'Impersonate terminato',
                [
                    'admin_id' => $event->impersonator->getAuthIdentifier(),
                    'target_id' => $event->impersonated->getAuthIdentifier(),
                ],
            );
        });
    }
}
