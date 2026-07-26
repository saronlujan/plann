<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('a guest can switch the interface language', function () {
    post('/locale', ['locale' => 'es'])->assertRedirect();

    expect(session('locale'))->toBe('es');

    get('/login')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('locale', 'es'));
});

test('an unsupported locale is rejected', function () {
    post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
});
