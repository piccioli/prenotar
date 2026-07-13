<?php

declare(strict_types=1);

it('la pagina di login unica /login risponde 200 con branding neutro CAI GR Lombardia', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('CAI GR Lombardia')
        ->assertDontSee('Prenotar — Admin')
        ->assertDontSee('Prenotar — GR Lombardia')
        ->assertDontSee('Prenotar — Sezione');
});
