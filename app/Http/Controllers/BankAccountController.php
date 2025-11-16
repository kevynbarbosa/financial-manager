<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the user's bank accounts with monthly summaries.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $transactionFilters = [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'account' => $request->filled('account') ? (int) $request->input('account') : null,
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
        ];

        $accounts = BankAccount::query()
            ->where('user_id', $user->id)
            ->withSum(
                ['transactions as monthly_income' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                        ->where('type', 'credit');
                }],
                'amount'
            )
            ->withSum(
                ['transactions as monthly_expense' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                        ->where('type', 'debit');
                }],
                'amount'
            )
            ->get();

        $transactions = BankTransaction::query()
            ->with('account:id,name,institution,user_id')
            ->whereHas('account', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($transactionFilters['search'], function (Builder $query, string $search) {
                $query->where('description', 'like', '%' . $search . '%');
            })
            ->when($transactionFilters['type'], function (Builder $query, string $type) {
                $query->where('type', $type);
            })
            ->when($transactionFilters['account'], function (Builder $query, int $accountId) {
                $query->where('bank_account_id', $accountId);
            })
            ->when($transactionFilters['start_date'], function (Builder $query, string $startDate) {
                $query->whereDate('occurred_at', '>=', Carbon::parse($startDate));
            })
            ->when($transactionFilters['end_date'], function (Builder $query, string $endDate) {
                $query->whereDate('occurred_at', '<=', Carbon::parse($endDate));
            })
            ->latest('occurred_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function (BankTransaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => (float) $transaction->amount,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'occurred_at' => optional($transaction->occurred_at)->toDateTimeString(),
                    'account' => [
                        'id' => $transaction->account->id,
                        'name' => $transaction->account->name,
                        'institution' => $transaction->account->institution,
                    ],
                ];
            });

        $accountsResource = $accounts->map(function (BankAccount $account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'institution' => $account->institution,
                'balance' => (float) $account->balance,
                'currency' => $account->currency,
                'accountType' => $account->account_type,
                'monthlyMovements' => [
                    'income' => (float) ($account->monthly_income ?? 0),
                    'expense' => (float) ($account->monthly_expense ?? 0),
                ],
            ];
        });

        $summary = [
            'totalBalance' => $accountsResource->sum('balance'),
            'totalIncome' => $accountsResource->sum(fn ($account) => $account['monthlyMovements']['income']),
            'totalExpense' => $accountsResource->sum(fn ($account) => $account['monthlyMovements']['expense']),
            'period' => [
                'start' => $startOfMonth->toDateString(),
                'end' => $endOfMonth->toDateString(),
            ],
        ];

        return Inertia::render('accounts/Index', [
            'accounts' => $accountsResource->values(),
            'summary' => $summary,
            'transactions' => $transactions,
            'transactionFilters' => $transactionFilters,
        ]);
    }
}
