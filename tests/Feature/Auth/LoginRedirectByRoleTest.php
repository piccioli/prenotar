<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('un admin viene reindirizzato a /admin dopo il login su /login', function (): void {
    $user = User::factory()->admin()->create();

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect('/admin');
});

it('un gr_manager viene reindirizzato a /gr dopo il login su /login', function (): void {
    $user = User::factory()->grManager()->create();

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect('/gr');
});

it('una sezione viene reindirizzata a /sezione dopo il login su /login', function (): void {
    $user = User::factory()->sezione()->create();

    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect('/sezione');
});
