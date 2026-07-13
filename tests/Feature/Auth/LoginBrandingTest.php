<?php

declare(strict_types=1);

it('la vecchia pagina di login /admin/login reindirizza al login unico /login', function (): void {
    $this->get('/admin/login')
        ->assertRedirect('/login');
});

it('la vecchia pagina di login /gr/login reindirizza al login unico /login', function (): void {
    $this->get('/gr/login')
        ->assertRedirect('/login');
});

it('la vecchia pagina di login /sezione/login reindirizza al login unico /login', function (): void {
    $this->get('/sezione/login')
        ->assertRedirect('/login');
});
