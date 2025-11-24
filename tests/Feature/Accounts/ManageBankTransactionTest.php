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

it('assigns category in bulk using contains without overwriting existing ones', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();
    $targetCategory = TransactionCategory::factory()->for($user)->create(['name' => 'Mercado']);
    $otherCategory = TransactionCategory::factory()->for($user)->create(['name' => 'Transporte']);

    $withoutCategory = BankTransaction::factory()->for($account)->create([
        'description' => 'Pagamento mercado bom',
        'transaction_category_id' => null,
        'category' => null,
    ]);

    $withCategory = BankTransaction::factory()->for($account)->create([
        'description' => 'Mercado central',
        'transaction_category_id' => $otherCategory->id,
        'category' => $otherCategory->name,
    ]);

    $nonMatching = BankTransaction::factory()->for($account)->create([
        'description' => 'Academia mensal',
        'transaction_category_id' => null,
        'category' => null,
    ]);

    actingAs($user);

    $this
        ->from(route('accounts.index'))
        ->post(route('transactions.category.bulk'), [
            'category_id' => $targetCategory->id,
            'match_type' => 'contains',
            'term' => 'mercado',
            'overwrite_existing' => false,
        ])
        ->assertRedirect(route('accounts.index'));

    $withoutCategory->refresh();
    $withCategory->refresh();
    $nonMatching->refresh();

    expect($withoutCategory->transaction_category_id)->toBe($targetCategory->id);
    expect($withoutCategory->category)->toBe($targetCategory->name);
    expect($withCategory->transaction_category_id)->toBe($otherCategory->id);
    expect($withCategory->category)->toBe($otherCategory->name);
    expect($nonMatching->transaction_category_id)->toBeNull();
});

it('overwrites categories when requested using exact match and keeps other users intact', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();
    $otherAccount = BankAccount::factory()->for($otherUser)->create();

    $newCategory = TransactionCategory::factory()->for($user)->create(['name' => 'Streaming']);
    $previousCategory = TransactionCategory::factory()->for($user)->create(['name' => 'Antigo']);

    $matching = BankTransaction::factory()->for($account)->create([
        'description' => 'Netflix mensal',
        'transaction_category_id' => $previousCategory->id,
        'category' => $previousCategory->name,
    ]);

    $otherUserTransaction = BankTransaction::factory()->for($otherAccount)->create([
        'description' => 'Netflix mensal',
        'transaction_category_id' => null,
        'category' => null,
    ]);

    actingAs($user);

    $this
        ->from(route('accounts.index'))
        ->post(route('transactions.category.bulk'), [
            'category_id' => $newCategory->id,
            'match_type' => 'exact',
            'term' => 'Netflix mensal',
            'overwrite_existing' => true,
        ])
        ->assertRedirect(route('accounts.index'));

    $matching->refresh();
    $otherUserTransaction->refresh();

    expect($matching->transaction_category_id)->toBe($newCategory->id);
    expect($matching->category)->toBe($newCategory->name);
    expect($otherUserTransaction->transaction_category_id)->toBeNull();
});
