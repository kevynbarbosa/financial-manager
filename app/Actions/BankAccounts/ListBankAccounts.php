<?php

namespace App\Actions\BankAccounts;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListBankAccounts
{
    public function __invoke(Request $request): array
    {
        $user = $request->user();

        $filters = $this->extractTransactionFilters($request);
        $dateRange = $this->resolveDateRange($filters);
        $filters['start_date'] = $filters['start_date'] ?: $dateRange['start']->toDateString();
        $filters['end_date'] = $filters['end_date'] ?: $dateRange['end']->toDateString();
        $categories = $this->getCategoryOptions($user->id);

        $accounts = $this->getAccountsWithRangeSums($user->id, $dateRange);
        $accountsResource = $this->transformAccounts($accounts);

        $transactions = $this->getTransactions($user->id, $filters);
        $summary = $this->buildSummary($accountsResource, $dateRange);

        return [
            'accounts' => $accountsResource->values(),
            'summary' => $summary,
            'transactions' => $transactions,
            'transactionFilters' => $filters,
            'transactionCategoryOptions' => $categories,
        ];
    }

    private function extractTransactionFilters(Request $request): array
    {
        $allowedSorts = ['occurred_at', 'amount', 'description'];
        $allowedDirections = ['asc', 'desc'];

        return [
            'search'     => $request->string('search')->toString(),
            'type'       => $request->string('type')->toString(),
            'account'    => $request->filled('account') ? (int) $request->input('account') : null,
            'start_date' => $request->string('start_date')->toString(),
            'end_date'   => $request->string('end_date')->toString(),
            'category'   => $request->string('category')->toString(),
            'sort'       => $this->validateEnum($request->string('sort')->toString(), $allowedSorts, 'occurred_at'),
            'direction'  => $this->validateEnum($request->string('direction')->toString(), $allowedDirections, 'desc'),
        ];
    }

    private function resolveDateRange(array $filters): array
    {
        $start = $filters['start_date'] ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $end = $filters['end_date'] ? Carbon::parse($filters['end_date']) : Carbon::now()->endOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [
            'start' => $start->copy()->startOfDay(),
            'end' => $end->copy()->endOfDay(),
        ];
    }

    private function validateEnum(?string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function getCategoryOptions(int $userId)
    {
        return TransactionCategory::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'icon'  => $c->icon,
                'color' => $c->color,
                'value' => (string) $c->id,
                'label' => $c->name,
            ]);
    }

    private function getAccountsWithRangeSums(int $userId, array $dateRange)
    {
        return BankAccount::where('user_id', $userId)
            ->withSum([
                'transactions as monthly_income' => fn($q) =>
                $this->rangeQuery($q, $dateRange, 'credit')
            ], 'amount')
            ->withSum([
                'transactions as monthly_expense' => fn($q) =>
                $this->rangeQuery($q, $dateRange, 'debit')
            ], 'amount')
            ->get();
    }

    private function rangeQuery(Builder $q, array $range, string $type): void
    {
        $q->whereBetween('occurred_at', [$range['start'], $range['end']])
            ->where('type', $type)
            ->where('is_transfer', false);
    }

    private function transformAccounts($accounts)
    {
        return $accounts->map(fn(BankAccount $a) => [
            'id'          => $a->id,
            'name'        => $a->name,
            'institution' => $a->institution,
            'balance'     => (float) $a->balance,
            'currency'    => $a->currency,
            'accountType' => $a->account_type,
            'monthlyMovements' => [
                'income'  => (float) ($a->monthly_income ?? 0),
                'expense' => (float) ($a->monthly_expense ?? 0),
            ],
        ]);
    }

    private function buildSummary($accounts, array $range): array
    {
        return [
            'totalBalance' => $accounts->sum('balance'),
            'totalIncome'  => $accounts->sum(fn($a) => $a['monthlyMovements']['income']),
            'totalExpense' => $accounts->sum(fn($a) => $a['monthlyMovements']['expense']),
            'period' => [
                'start' => $range['start']->toDateString(),
                'end'   => $range['end']->toDateString(),
            ],
        ];
    }

    private function getTransactions(int $userId, array $filters)
    {
        return BankTransaction::query()
            ->with([
                'account:id,name,institution,user_id',
                'categoryRelation:id,name,icon,color',
            ])
            ->whereHas('account', fn($q) => $q->where('user_id', $userId))
            ->tap(fn($q) => $this->applyTransactionFilters($q, $filters))
            ->orderBy($filters['sort'], $filters['direction'])
            ->paginate(10)
            ->withQueryString()
            ->through(fn(BankTransaction $t) => $this->transformTransaction($t));
    }

    private function applyTransactionFilters(Builder $q, array $filters): void
    {
        $q->when($filters['search'], fn($q, $search) =>
        $q->where('description', 'like', "%$search%"));

        $q->when($filters['type'], fn($q, $type) =>
        $q->where('type', $type));

        $q->when($filters['account'], fn($q, $id) =>
        $q->where('bank_account_id', $id));

        $q->when($filters['start_date'], fn($q, $date) =>
        $q->whereDate('occurred_at', '>=', Carbon::parse($date)));

        $q->when($filters['end_date'], fn($q, $date) =>
        $q->whereDate('occurred_at', '<=', Carbon::parse($date)));

        $q->when($filters['category'], function ($q, $cat) {
            $cat === 'none'
                ? $q->whereNull('transaction_category_id')
                : $q->where('transaction_category_id', (int) $cat);
        });
    }

    private function transformTransaction(BankTransaction $t): array
    {
        return [
            'id'          => $t->id,
            'description' => $t->description,
            'amount'      => (float) $t->amount,
            'type'        => $t->type,
            'is_transfer' => (bool) $t->is_transfer,
            'category'    => $this->resolveCategory($t),
            'occurred_at' => optional($t->occurred_at)->toDateTimeString(),
            'account' => [
                'id'          => $t->account->id,
                'name'        => $t->account->name,
                'institution' => $t->account->institution,
            ],
        ];
    }

    private function resolveCategory(BankTransaction $t): ?array
    {
        if ($t->categoryRelation) {
            return [
                'id'    => $t->categoryRelation->id,
                'name'  => $t->categoryRelation->name,
                'icon'  => $t->categoryRelation->icon,
                'color' => $t->categoryRelation->color,
            ];
        }

        if ($t->category) {
            return [
                'id'    => null,
                'name'  => $t->category,
                'icon'  => null,
                'color' => null,
            ];
        }

        return null;
    }
}
