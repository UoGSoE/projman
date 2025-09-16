<?php

use App\Livewire\Profile;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(Profile::class)
        ->assertStatus(200);
});
