<?php

declare(strict_types=1);

use App\Filament\Pages\FirstAccessPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('utente con email fallback senza contact_email viene rediretto a first-access-page', function (): void {
    $user = User::factory()->sezione()->withFallbackEmail()->create(['contact_email' => null]);

    $this->actingAs($user)
        ->get('/sezione')
        ->assertRedirect('/sezione/first-access-page');
});

it('utente con email reale NON viene rediretto', function (): void {
    $user = User::factory()->sezione()->create([
        'email_is_fallback' => false,
        'contact_email' => null,
    ]);

    $this->actingAs($user)
        ->get('/sezione')
        ->assertOk();
});

it('utente con email fallback MA contact_email già impostata NON viene rediretto', function (): void {
    $user = User::factory()->sezione()->withFallbackEmail()->withContactEmail('reale@email.it')->create();

    $this->actingAs($user)
        ->get('/sezione')
        ->assertOk();
});

it('admin senza email fallback non viene mai rediretto', function (): void {
    $user = User::factory()->admin()->create(['email_is_fallback' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

it('il form first-access salva la contact_email', function (): void {
    $user = User::factory()->sezione()->withFallbackEmail()->create(['contact_email' => null]);

    $this->actingAs($user);

    Livewire::test(FirstAccessPage::class)
        ->set('contact_email', 'reale@email.it')
        ->call('save');

    expect($user->fresh()->contact_email)->toBe('reale@email.it');
});
