<?php

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('the login page renders for guests', function () {
    $this->get(route('login'))->assertOk();
});
