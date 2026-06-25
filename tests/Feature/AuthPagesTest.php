<?php

it('renders the login page', function () {
    $this->get(route('login'))->assertOk();
});

it('renders the logged-out page', function () {
    $this->get(route('logged_out'))->assertOk();
});
