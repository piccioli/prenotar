<?php

declare(strict_types=1);

it('redirects the home URL to the admin backend', function () {
    $this->get('/')->assertRedirect('/admin');
});
