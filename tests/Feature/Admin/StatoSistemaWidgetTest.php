<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\StatoSistemaWidget;
use App\Models\ExcelImport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = User::factory()->admin()->create();
});

test('conta utenti attivi, totali e disattivati', function (): void {
    User::factory()->count(2)->create(['is_active' => true]);
    User::factory()->count(3)->create(['is_active' => false]);

    $widget = new StatoSistemaWidget;

    expect($widget->getUtentiTotali())->toBe(1 + 2 + 3)
        ->and($widget->getUtentiAttivi())->toBe(1 + 2)
        ->and($widget->getUtentiDisattivati())->toBe(3);
});

test('espone l\'ultimo import Excel per data di creazione', function (): void {
    ExcelImport::factory()->create(['created_at' => now()->subDays(5)]);
    $ultimo = ExcelImport::factory()->create(['created_at' => now()->subDay(), 'righe_in_errore' => 2]);

    $widget = new StatoSistemaWidget;

    expect($widget->getUltimoImport()->id)->toBe($ultimo->id);
});

test('conta solo i job falliti negli ultimi 7 giorni', function (): void {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'errore recente',
        'failed_at' => now()->subDays(2),
    ]);
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'errore vecchio',
        'failed_at' => now()->subDays(10),
    ]);

    $widget = new StatoSistemaWidget;

    expect($widget->getErroriRecenti())->toBe(1);
});

test('mostra gli ultimi eventi di sistema dall\'audit log esistente', function (): void {
    activity('user')->causedBy($this->admin)->log('user.reset_password');

    Livewire::actingAs($this->admin)
        ->test(StatoSistemaWidget::class)
        ->assertSee('Ultimi eventi di sistema')
        ->assertSee($this->admin->name)
        ->assertSee('user.reset_password');

    expect(Activity::count())->toBeGreaterThan(0);
});
