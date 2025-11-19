<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\CategoryLimit;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the category limits overview with spending data', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->for($user)->create([
        'name' => 'Alimentação',
    ]);
    $account = BankAccount::factory()->for($user)->create();

    CategoryLimit::factory()
        ->for($user)
        ->for($category, 'category')
        ->create(['monthly_limit' => 500]);

    Carbon::setTestNow(Carbon::now()->startOfMonth()->addDays(5));

    BankTransaction::factory()
        ->for($account, 'account')
        ->debit()
        ->create([
            'transaction_category_id' => $category->id,
            'amount' => 200,
            'occurred_at' => now(),
            'is_transfer' => false,
        ]);

    actingAs($user);

    $this->get(route('category-limits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('category-limits/Index')
            ->where('categories.0.name', 'Alimentação')
            ->where('categories.0.limit.monthly_limit', 500.0)
            ->where('categories.0.spent', 200.0)
        );

    Carbon::setTestNow();
});

it('allows a user to create and update limits for their categories', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->for($user)->create();

    actingAs($user);

    $this->post(route('category-limits.store'), [
        'transaction_category_id' => $category->id,
        'monthly_limit' => 750,
    ])->assertRedirect(route('category-limits.index'));

    $limit = CategoryLimit::first();

    expect($limit)
        ->not->toBeNull()
        ->and($limit->monthly_limit)->toBe('750.00');

    $this->put(route('category-limits.update', $limit), [
        'monthly_limit' => 900,
    ])->assertRedirect(route('category-limits.index'));

    $this->assertDatabaseHas('category_limits', [
        'id' => $limit->id,
        'transaction_category_id' => $category->id,
        'monthly_limit' => 900,
    ]);
});

it('prevents users from changing limits they do not own', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = TransactionCategory::factory()->for($owner)->create();
    $limit = CategoryLimit::factory()
        ->for($owner)
        ->for($category, 'category')
        ->create(['monthly_limit' => 400]);

    actingAs($intruder);

    $this->post(route('category-limits.store'), [
        'transaction_category_id' => $category->id,
        'monthly_limit' => 300,
    ])->assertSessionHasErrors('transaction_category_id');

    $this->put(route('category-limits.update', $limit), [
        'monthly_limit' => 100,
    ])->assertForbidden();

    $this->delete(route('category-limits.destroy', $limit))->assertForbidden();
});
