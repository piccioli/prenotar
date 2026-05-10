<?php

declare(strict_types=1);

namespace App\Providers;

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
    }
}
