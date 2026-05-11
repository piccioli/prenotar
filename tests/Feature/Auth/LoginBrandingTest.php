<?php

declare(strict_types=1);

it('la pagina di login /admin/login risponde 200 e contiene Prenotar', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Prenotar');
});

it('la pagina di login /gr/login risponde 200', function (): void {
    $this->get('/gr/login')
        ->assertOk();
});

it('la pagina di login /sezione/login risponde 200', function (): void {
    $this->get('/sezione/login')
        ->assertOk();
});
