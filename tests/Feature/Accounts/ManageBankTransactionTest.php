<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\TransactionCategory;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates a transaction description, category and transfer flag', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();
    $transaction = BankTransaction::factory()->for($account)->create([
        'description' => 'Pagamento antigo',
        'is_transfer' => false,
    ]);
    $category = TransactionCategory::factory()->for($user)->create(['name' => 'Serviços']);

    actingAs($user);

    $response = $this
        ->from(route('accounts.index'))
        ->put(route('transactions.update', $transaction), [
            'description' => 'Pagamento atualizado',
            'category_id' => $category->id,
            'is_transfer' => true,
        ]);

    $response->assertRedirect(route('accounts.index'));

    $transaction->refresh();

    expect($transaction->description)->toBe('Pagamento atualizado');
    expect($transaction->transaction_category_id)->toBe($category->id);
    expect($transaction->is_transfer)->toBeTrue();
});

it('prevents users from updating transactions they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = BankAccount::factory()->for($owner)->create();
    $transaction = BankTransaction::factory()->for($account)->create();

    actingAs($otherUser);

    $this
        ->put(route('transactions.update', $transaction), [
            'description' => 'Tentativa inválida',
            'category_id' => null,
            'is_transfer' => false,
        ])
        ->assertForbidden();
});

it('updates transaction category via quick action endpoint', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();
    $transaction = BankTransaction::factory()->for($account)->create();
    $category = TransactionCategory::factory()->for($user)->create();

    actingAs($user);

    $this->put(route('transactions.category.update', $transaction), [
        'category_id' => $category->id,
    ])->assertRedirect();

    $transaction->refresh();
    expect($transaction->transaction_category_id)->toBe($category->id);
});
