<?php

declare(strict_types=1);

test('la ruta raíz redirige al dashboard', function (): void {
    $this->get('/')
        ->assertRedirect(route('dashboard'));
});

test('el dashboard redirige al login cuando no está autenticado', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
