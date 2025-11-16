<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Carbon\Carbon;
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
        ]);
    }
}
