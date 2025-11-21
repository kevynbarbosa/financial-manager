<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportOfxRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Enums\AccountType;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\Ofx\OfxImportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal as ModalResponse;

use function InertiaUI\Modal\back_from_modal;

class BankAccountController extends Controller
{
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
            'category' => $request->string('category')->toString(),
        ];

        $allowedSorts = ['occurred_at', 'amount', 'description'];
        $allowedDirections = ['asc', 'desc'];

        $requestedSort = $request->string('sort')->toString();
        $requestedDirection = $request->string('direction')->toString();

        $transactionFilters['sort'] = in_array($requestedSort, $allowedSorts, true) ? $requestedSort : 'occurred_at';
        $transactionFilters['direction'] = in_array($requestedDirection, $allowedDirections, true) ? $requestedDirection : 'desc';

        $transactionCategories = TransactionCategory::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color'])
            ->map(fn(TransactionCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'value' => (string) $category->id,
                'label' => $category->name,
            ])
            ->values();

        $accounts = BankAccount::query()
            ->where('user_id', $user->id)
            ->withSum(
                ['transactions as monthly_income' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                        ->where('type', 'credit')
                        ->where('is_transfer', false);
                }],
                'amount'
            )
            ->withSum(
                ['transactions as monthly_expense' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                        ->where('type', 'debit')
                        ->where('is_transfer', false);
                }],
                'amount'
            )
            ->get();

        $transactions = BankTransaction::query()
            ->with([
                'account:id,name,institution,user_id',
                'categoryRelation:id,name,icon,color',
            ])
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
            ->when($transactionFilters['category'], function (Builder $query, string $category) {
                if ($category === 'none') {
                    $query->whereNull('transaction_category_id');
                } else {
                    $query->where('transaction_category_id', (int) $category);
                }
            })
            ->orderBy($transactionFilters['sort'], $transactionFilters['direction'])
            ->paginate(10)
            ->withQueryString()
            ->through(function (BankTransaction $transaction) {
                $category = null;

                if ($transaction->categoryRelation) {
                    $category = [
                        'id' => $transaction->categoryRelation->id,
                        'name' => $transaction->categoryRelation->name,
                        'icon' => $transaction->categoryRelation->icon,
                        'color' => $transaction->categoryRelation->color,
                    ];
                } elseif ($transaction->category) {
                    $category = [
                        'id' => null,
                        'name' => $transaction->category,
                        'icon' => null,
                        'color' => null,
                    ];
                }

                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'amount' => (float) $transaction->amount,
                    'type' => $transaction->type,
                    'is_transfer' => (bool) $transaction->is_transfer,
                    'category' => $category,
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
            'totalIncome' => $accountsResource->sum(fn($account) => $account['monthlyMovements']['income']),
            'totalExpense' => $accountsResource->sum(fn($account) => $account['monthlyMovements']['expense']),
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
            'transactionCategoryOptions' => $transactionCategories,
        ]);
    }

    public function importOfx(ImportOfxRequest $request, OfxImportService $importService): RedirectResponse
    {
        try {
            $result = $importService->import($request->user(), $request->file('ofx_file'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('accounts.index')
                ->with('error', 'Não conseguimos importar o arquivo OFX. Verifique o arquivo e tente novamente.');
        }

        $message = sprintf(
            'Importação concluída para %s: %d nova(s) transação(ões) e %d já existente(s).',
            $result['account']->name,
            $result['created'],
            $result['skipped']
        );

        return redirect()
            ->route('accounts.index')
            ->with('success', $message);
    }

    public function edit(Request $request, BankAccount $account): ModalResponse
    {
        return Inertia::modal('accounts/modals/EditBankAccount', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'institution' => $account->institution,
                'account_type' => $account->account_type,
                'account_number' => $account->account_number,
                'currency' => $account->currency,
            ],
            'accountTypes' => AccountType::options(),
        ])->baseRoute('accounts.index');
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return back_from_modal()->with('success', 'Conta atualizada com sucesso.');
    }
}
