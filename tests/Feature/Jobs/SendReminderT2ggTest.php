<?php

declare(strict_types=1);

use App\Jobs\SendReminderT2gg;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Notifications\ReminderT2ggNotification;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create();
    $this->sezione = User::factory()->sezione()->create();
});

test('invia reminder T-2gg al GR per prenotazione in scadenza', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $sogliaGiorni = (int) ceil($settings->ore_minime_richiesta_assicurazione / 24);

    Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_ritiro' => today()->addDays($sogliaGiorni),
    ]);

    (new SendReminderT2gg)->handle($settings);

    Notification::assertSentOnDemand(ReminderT2ggNotification::class);
});

test('non invia reminder T-2gg se flag già valorizzato (one-shot)', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $sogliaGiorni = (int) ceil($settings->ore_minime_richiesta_assicurazione / 24);

    Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_ritiro' => today()->addDays($sogliaGiorni),
        'reminder_t2gg_inviato_at' => now()->subDay(),
    ]);

    (new SendReminderT2gg)->handle($settings);

    Notification::assertNothingSent();
});

test('non invia reminder T-2gg se emails_notifiche_gr è vuoto', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $settings->emails_notifiche_gr = [];
    $sogliaGiorni = (int) ceil($settings->ore_minime_richiesta_assicurazione / 24);

    Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_ritiro' => today()->addDays($sogliaGiorni),
    ]);

    (new SendReminderT2gg)->handle($settings);

    Notification::assertNothingSent();
});
