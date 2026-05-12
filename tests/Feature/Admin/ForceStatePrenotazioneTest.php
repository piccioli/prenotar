<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->sezione = User::factory()->sezione()->create();
});

test('admin force state aggiorna status e crea history + audit log', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    app(AuditLogger::class)->logAdminAction(
        'prenotazione.force_state',
        $pren,
        'Test forzatura stato per verifica',
        ['from' => PrenotazioneStatus::Inviata->value, 'to' => PrenotazioneStatus::Concluso->value],
    );

    $pren->update(['status' => PrenotazioneStatus::Concluso]);
    PrenotazioneHistory::create([
        'prenotazione_id' => $pren->id,
        'user_id' => $this->admin->id,
        'status_from' => PrenotazioneStatus::Inviata,
        'status_to' => PrenotazioneStatus::Concluso,
        'note' => '[FORCE STATE] Test forzatura stato per verifica',
        'created_at' => now(),
    ]);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Concluso);

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->note)->toContain('[FORCE STATE]');

    $log = Activity::where('log_name', 'admin')->where('event', 'prenotazione.force_state')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['motivo'])->toBe('Test forzatura stato per verifica');
});

test('hard delete: il record è rimosso ma l\'audit log conserva lo snapshot', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'nome_evento' => 'Evento da cancellare',
    ]);
    $prenId = $pren->id;

    app(AuditLogger::class)->logAdminAction(
        'prenotazione.hard_delete',
        $pren,
        'Spam — eliminazione test',
        ['snapshot' => ['id' => $pren->id, 'nome_evento' => $pren->nome_evento]],
    );

    $pren->forceDelete();

    expect(Prenotazione::withTrashed()->find($prenId))->toBeNull();

    $log = Activity::where('log_name', 'admin')->where('event', 'prenotazione.hard_delete')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['motivo'])->toBe('Spam — eliminazione test')
        ->and($log->properties['snapshot']['nome_evento'])->toBe('Evento da cancellare');
});
