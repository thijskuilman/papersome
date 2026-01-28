<?php

use App\Livewire\RssFeedVerify;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(RssFeedVerify::class)
        ->assertStatus(200);
});
