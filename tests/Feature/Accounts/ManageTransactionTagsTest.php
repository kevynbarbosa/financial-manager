<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Tag;
use App\Models\TransactionCategory;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates a transaction description and tags for the authenticated user', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();
    $transaction = BankTransaction::factory()->for($account)->create([
        'description' => 'Pagamento antigo',
    ]);
    $existingTag = Tag::factory()->for($user)->create(['name' => 'Essenciais']);

    actingAs($user);

    $category = TransactionCategory::factory()->for($user)->create(['name' => 'Serviços']);

    $response = $this
        ->from(route('accounts.index'))
        ->put(route('transactions.tags.update', $transaction), [
            'description' => 'Pagamento atualizado',
            'tags' => [$existingTag->name, 'Educação'],
            'category_id' => $category->id,
        ]);

    $response->assertRedirect(route('accounts.index'));

    $transaction->refresh();
    $transaction->load('tags');

    expect($transaction->description)->toBe('Pagamento atualizado');
    expect($transaction->transaction_category_id)->toBe($category->id);
    expect($transaction->tags)->toHaveCount(2);
    expect($transaction->tags->pluck('name')->all())->toEqualCanonicalizing(['Essenciais', 'Educação']);

    $this->assertDatabaseHas('tags', [
        'user_id' => $user->id,
        'name' => 'Educação',
    ]);
});

it('prevents users from updating transactions they do not own', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $account = BankAccount::factory()->for($owner)->create();
    $transaction = BankTransaction::factory()->for($account)->create();

    actingAs($otherUser);

    $this
        ->put(route('transactions.tags.update', $transaction), [
            'description' => 'Tentativa inválida',
            'tags' => ['Fraude'],
            'category_id' => null,
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
