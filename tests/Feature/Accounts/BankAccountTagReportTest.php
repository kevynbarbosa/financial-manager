<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('returns tagged transactions data and tag reports on the accounts screen', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create();

    $incomeTag = Tag::factory()->for($user)->create(['name' => 'Receitas']);
    $expenseTag = Tag::factory()->for($user)->create(['name' => 'Impostos']);

    $recentCredit = BankTransaction::factory()->for($account)->credit()->create([
        'amount' => 5000,
        'occurred_at' => now(),
    ]);
    $recentCredit->tags()->attach($incomeTag);

    $olderDebit = BankTransaction::factory()->for($account)->debit()->create([
        'amount' => 1200,
        'occurred_at' => now()->subDay(),
    ]);
    $olderDebit->tags()->attach([$incomeTag->id, $expenseTag->id]);

    actingAs($user);

    $this->get(route('accounts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('accounts/Index')
            ->has('transactions.data', 2)
            ->where('transactions.data.0.tags.0.name', $incomeTag->name)
            ->where('tagReports.totals.credit', 5000.0)
            ->where('tagReports.totals.debit', 1200.0)
            ->where('tagReports.breakdown.0.name', $incomeTag->name)
            ->where('tagReports.breakdown.0.credit', 5000.0)
            ->where('tagReports.breakdown.0.debit', 1200.0)
            ->where('tagReports.breakdown.1.name', $expenseTag->name)
            ->where('tagReports.breakdown.1.debit', 1200.0)
        );
});
