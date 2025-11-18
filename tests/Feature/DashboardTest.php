<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows a spending report grouped by category on the dashboard', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $account = BankAccount::factory()->for($user)->create();
    $otherAccount = BankAccount::factory()->for($otherUser)->create();

    $food = TransactionCategory::factory()->for($user)->create([
        'name' => 'Alimentação',
        'icon' => 'shopping-bag',
    ]);

    $transport = TransactionCategory::factory()->for($user)->create([
        'name' => 'Transporte',
        'icon' => 'car',
    ]);

    $recentDate = now()->subDays(5);

    BankTransaction::factory()->for($account)->debit()->create([
        'transaction_category_id' => $food->id,
        'amount' => 150,
        'occurred_at' => $recentDate,
    ]);

    BankTransaction::factory()->for($account)->debit()->create([
        'transaction_category_id' => $transport->id,
        'amount' => 50,
        'occurred_at' => $recentDate->copy()->subDay(),
    ]);

    BankTransaction::factory()->for($account)->credit()->create([
        'transaction_category_id' => $food->id,
        'amount' => 9999,
        'occurred_at' => $recentDate,
    ]);

    BankTransaction::factory()->for($account)->debit()->create([
        'transaction_category_id' => $food->id,
        'amount' => 999,
        'occurred_at' => $recentDate,
        'is_transfer' => true,
    ]);

    BankTransaction::factory()->for($otherAccount)->debit()->create([
        'transaction_category_id' => $food->id,
        'amount' => 800,
        'occurred_at' => $recentDate,
    ]);

    actingAs($user);

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('categorySpending.total', 200.0)
            ->has('categorySpending.categories', 2)
            ->where('categorySpending.categories.0.name', $food->name)
            ->where('categorySpending.categories.0.total', 150.0)
            ->where('categorySpending.categories.1.name', $transport->name)
            ->where('categorySpending.categories.1.total', 50.0)
        );
});

it('filters the dashboard report using the provided date range', function () {
    Carbon::setTestNow('2024-03-01 12:00:00');

    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();
    $category = TransactionCategory::factory()->for($user)->create([
        'name' => 'Viagens',
        'icon' => 'gift',
    ]);

    BankTransaction::factory()->for($account)->debit()->create([
        'transaction_category_id' => $category->id,
        'amount' => 250,
        'occurred_at' => Carbon::parse('2024-02-28'),
    ]);

    BankTransaction::factory()->for($account)->debit()->create([
        'transaction_category_id' => $category->id,
        'amount' => 400,
        'occurred_at' => Carbon::parse('2023-12-31'),
    ]);

    actingAs($user);

    $start = '2024-02-01';
    $end = '2024-02-29';

    $this->get(route('dashboard', [
        'start_date' => $start,
        'end_date' => $end,
    ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('filters.start_date', $start)
            ->where('filters.end_date', $end)
            ->where('categorySpending.total', 250.0)
            ->has('categorySpending.categories', 1)
            ->where('categorySpending.categories.0.total', 250.0)
        );

    Carbon::setTestNow();
});
