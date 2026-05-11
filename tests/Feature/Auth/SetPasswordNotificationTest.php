<?php

declare(strict_types=1);

use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Notification::fake();
});

it('invia notifica solo al destinatario per user con email reale', function (): void {
    $user = User::factory()->sezione()->create(['email_is_fallback' => false]);

    $user->notify(new SetPasswordNotification($user));

    Notification::assertSentTo($user, SetPasswordNotification::class, function ($notification, $channels) use ($user) {
        $mail = $notification->toMail($user);
        expect($mail->cc)->toBeEmpty();

        return true;
    });
});

it('aggiunge CC sezione padre per sottosezione con email fallback', function (): void {
    $sezione = Sezione::factory()->create(['email' => 'sezionepadre@cai.it']);
    $sottosezione = Sottosezione::factory()->create(['sezione_id' => $sezione->id]);
    $user = User::factory()->create([
        'email_is_fallback' => true,
        'sottosezione_id' => $sottosezione->id,
        'sezione_id' => null,
    ]);
    $user->assignRole('sezione');
    $user->load('sottosezione.sezione');

    $user->notify(new SetPasswordNotification($user));

    Notification::assertSentTo($user, SetPasswordNotification::class, function ($notification, $channels) use ($user) {
        $mail = $notification->toMail($user);
        $ccEmails = array_column($mail->cc, 0);
        expect($ccEmails)->toContain('sezionepadre@cai.it');

        return true;
    });
});

it('aggiunge CC admin GR per qualunque user con email fallback', function (): void {
    $user = User::factory()->sezione()->withFallbackEmail()->create();
    $user->load('sezione');

    $user->notify(new SetPasswordNotification($user));

    Notification::assertSentTo($user, SetPasswordNotification::class, function ($notification, $channels) use ($user) {
        $mail = $notification->toMail($user);
        $ccEmails = array_column($mail->cc, 0);
        // GrSettings.emails_notifiche_gr default: gr_cai_lombardia@cai.it
        expect($ccEmails)->toContain('gr_cai_lombardia@cai.it');

        return true;
    });
});

it('il subject è corretto', function (): void {
    $user = User::factory()->sezione()->create();

    $user->notify(new SetPasswordNotification($user));

    Notification::assertSentTo($user, SetPasswordNotification::class, function ($notification, $channels) use ($user) {
        $mail = $notification->toMail($user);
        expect($mail->subject)->toBe('Imposta la tua password — Prenotar CAI Lombardia');

        return true;
    });
});
