<?php

use App\Models\BankAccount;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('allows access when the user owns the account', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();

    actingAs($user)
        ->get(route('accounts.edit', $account))
        ->assertOk();
});

it('forbids access when the user does not own the account', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $account = BankAccount::factory()->for($owner)->create();

    actingAs($other)
        ->get(route('accounts.edit', $account))
        ->assertForbidden();
});
