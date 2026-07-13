<?php

declare(strict_types=1);

it('trans(validation.*) risolve in italiano invece di restituire la chiave grezza', function (): void {
    expect(trans('validation.required', ['attribute' => 'indirizzo evento']))
        ->toBe('Il campo indirizzo evento è obbligatorio.')
        ->not->toBe('validation.required');

    expect(trans('validation.email', ['attribute' => 'email']))
        ->toBe('Il campo email deve essere un indirizzo email valido.');
});
