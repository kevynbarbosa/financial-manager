<?php

use App\Models\BankAccount;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates a bank account for the authenticated user', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create([
        'name' => 'Conta antiga',
        'institution' => 'Itaú',
        'account_type' => 'checking',
        'currency' => 'BRL',
    ]);

    actingAs($user);

    $response = $this->put(route('accounts.update', $account), [
        'name' => 'Conta atualizada',
        'institution' => 'Nubank',
        'account_type' => 'savings',
        'account_number' => '1234-5',
        'currency' => 'USD',
    ]);

    $response->assertRedirect(route('accounts.index'));

    $account->refresh();

    expect($account->name)->toBe('Conta atualizada');
    expect($account->institution)->toBe('Nubank');
    expect($account->account_type)->toBe('savings');
    expect($account->account_number)->toBe('1234-5');
    expect($account->currency)->toBe('USD');
});

it('prevents users from editing accounts they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = BankAccount::factory()->for($owner)->create();

    actingAs($otherUser);

    $this->put(route('accounts.update', $account), [
        'name' => 'Conta Inválida',
        'institution' => 'Teste',
        'account_type' => 'checking',
        'account_number' => '000',
        'currency' => 'BRL',
    ])->assertForbidden();
});
