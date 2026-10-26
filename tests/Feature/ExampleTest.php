<?php

it('redirects the home page to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
