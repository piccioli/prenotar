<?php

declare(strict_types=1);

use App\Jobs\SendReminderT10;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Notifications\ReminderT10Notification;
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

test('invia reminder T-10 a sezione con documento mancante', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $giorni = $settings->giorni_minimi_caricamento_documenti; // default 10

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_inizio_evento' => today()->addDays($giorni),
    ]);

    (new SendReminderT10)->handle($settings);

    Notification::assertSentTo($this->sezione, ReminderT10Notification::class);

    $pren->refresh();
    expect($pren->reminder_t10_inviato_at)->not->toBeNull();
});

test('non invia reminder se flag già valorizzato (one-shot)', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $giorni = $settings->giorni_minimi_caricamento_documenti;

    Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_inizio_evento' => today()->addDays($giorni),
        'reminder_t10_inviato_at' => now()->subDay(),
    ]);

    (new SendReminderT10)->handle($settings);

    Notification::assertNothingSent();
});

test('non invia reminder se status non è Approvata', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $giorni = $settings->giorni_minimi_caricamento_documenti;

    Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_inizio_evento' => today()->addDays($giorni),
    ]);

    (new SendReminderT10)->handle($settings);

    Notification::assertNothingSent();
});
