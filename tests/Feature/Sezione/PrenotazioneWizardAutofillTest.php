<?php

declare(strict_types=1);

use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\CreatePrenotazione;
use App\Models\Prenotazione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->sezione()->create();
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

/** @return list<string> */
function autofillActionNames(): array
{
    return [
        'autofill_manuale',
        'autofill_quando_dove',
        'autofill_evento',
        'autofill_logistica_trasporto',
        'autofill_responsabile_in_loco',
    ];
}

test('in ambiente local i bottoni di autofill sono visibili e compilano il wizard fino a un submit valido', function (): void {
    app()->instance('env', 'local');
    actingAs($this->user);

    $livewire = Livewire::test(CreatePrenotazione::class);

    foreach (autofillActionNames() as $actionName) {
        $livewire
            ->assertFormComponentActionVisible("{$actionName}Action", $actionName)
            ->callFormComponentAction("{$actionName}Action", $actionName);
    }

    $livewire->call('create')->assertHasNoFormErrors();

    expect(Prenotazione::count())->toBe(1);
});

test('fuori da ambiente local i bottoni di autofill non compaiono nel wizard e non hanno alcun effetto se richiamati', function (): void {
    actingAs($this->user);

    // Filament esclude le Actions con visible() falso da ComponentContainer::getComponent()
    // (withHidden di default false): il bottone non e' solo nascosto ma assente dall'albero
    // dello schema, quindi una mountFormComponentAction "forzata" non trova nulla da montare.
    Livewire::test(CreatePrenotazione::class)
        ->assertDontSee('Compila con dati di test')
        ->call('mountFormComponentAction', 'autofill_manualeAction', 'autofill_manuale')
        ->assertStatus(200);

    expect(Prenotazione::count())->toBe(0);
});

test('la action di autofill abortisce con 404 se il suo callback viene invocato fuori da ambiente local', function (): void {
    /** @var Actions $actionsComponent */
    $actionsComponent = (new ReflectionMethod(PrenotazioneResource::class, 'autofillAction'))
        ->invoke(null, 'autofill_probe', fn () => null);

    $actionContainer = $actionsComponent->getChildComponents()[0];
    $callback = $actionContainer->getAction('autofill_probe')->getActionFunction();

    expect(fn () => $callback(Mockery::mock(Set::class), Mockery::mock(Get::class)))
        ->toThrow(NotFoundHttpException::class);
});
