<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\PrenotazioneApprovata;
use App\Events\PrenotazioneInviata;
use App\Events\PrenotazioneRifiutata;
use App\Events\PrenotazioneTorreRiassegnata;
use App\Events\UserSetPasswordRequested;
use App\Listeners\SendPrenotazioneApprovataNotification;
use App\Listeners\SendPrenotazioneInviataNotification;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
    }
}
