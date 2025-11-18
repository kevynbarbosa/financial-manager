<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('sorts bank transactions by the requested column and direction', function (string $sort, string $direction, string $expectedFirst) {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();

    BankTransaction::factory()->for($account)->create([
        'description' => 'Academia Premium',
        'amount' => 1500,
        'occurred_at' => now()->subDay(),
    ]);

    BankTransaction::factory()->for($account)->create([
        'description' => 'Mercado Central',
        'amount' => 3200,
        'occurred_at' => now()->subHours(3),
    ]);

    BankTransaction::factory()->for($account)->create([
        'description' => 'Uber Comfort',
        'amount' => 740,
        'occurred_at' => now()->subDays(2),
    ]);

    actingAs($user);

    $this->get(route('accounts.index', [
        'sort' => $sort,
        'direction' => $direction,
    ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('accounts/Index')
            ->where('transactions.data.0.description', $expectedFirst)
            ->where('transactionFilters.sort', $sort)
            ->where('transactionFilters.direction', $direction)
        );
})->with([
    'description ascending' => ['description', 'asc', 'Academia Premium'],
    'amount descending' => ['amount', 'desc', 'Mercado Central'],
    'date ascending' => ['occurred_at', 'asc', 'Uber Comfort'],
]);
