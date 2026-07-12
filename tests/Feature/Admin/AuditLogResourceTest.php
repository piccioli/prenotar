<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\AuditLogResource;
use App\Models\Torre;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin può vedere la lista audit log con colonne Quando/Chi/Cosa/Tipo', function (): void {
    $admin = User::factory()->admin()->create();
    $torre = Torre::factory()->create(['nome' => 'Torre Test']);

    actingAs($admin);
    app(AuditLogger::class)->logAdminAction(
        'prenotazione.hard_delete',
        $torre,
        'Eliminazione definitiva di test',
    );

    actingAs($admin)
        ->get(AuditLogResource::getUrl('index', panel: 'admin'))
        ->assertSuccessful()
        ->assertSee('Eliminazione definitiva di test')
        ->assertSee('Eliminazione definitiva');
});

test('un evento automatico senza motivo mostra entità e azione in italiano', function (): void {
    $admin = User::factory()->admin()->create();
    $torre = Torre::factory()->create(['nome' => 'Torre Originale']);
    $torre->update(['nome' => 'Torre Aggiornata']);

    actingAs($admin)
        ->get(AuditLogResource::getUrl('index', panel: 'admin'))
        ->assertSuccessful()
        ->assertSee('Torre #'.$torre->id.' aggiornata');
});
