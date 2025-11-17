<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportOfxRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Tag;
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
            ->with([
                'account:id,name,institution,user_id',
                'tags:id,name',
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
                    'tags' => $transaction->tags->map(fn (Tag $tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ])->values()->all(),
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

        $tagReports = $this->buildTagReports($user);

        return Inertia::render('accounts/Index', [
            'accounts' => $accountsResource->values(),
            'summary' => $summary,
            'transactions' => $transactions,
            'transactionFilters' => $transactionFilters,
            'tagReports' => $tagReports,
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

    protected function buildTagReports(User $user): array
    {
        $tagSummary = Tag::query()
            ->select('tags.id', 'tags.name')
            ->selectRaw("SUM(CASE WHEN bank_transactions.type = 'credit' THEN bank_transactions.amount ELSE 0 END) as credit_total")
            ->selectRaw("SUM(CASE WHEN bank_transactions.type = 'debit' THEN bank_transactions.amount ELSE 0 END) as debit_total")
            ->join('bank_transaction_tag', 'tags.id', '=', 'bank_transaction_tag.tag_id')
            ->join('bank_transactions', 'bank_transaction_tag.bank_transaction_id', '=', 'bank_transactions.id')
            ->where('tags.user_id', $user->id)
            ->groupBy('tags.id', 'tags.name')
            ->get();

        $breakdown = $tagSummary
            ->map(function ($tag) {
                $credit = (float) $tag->credit_total;
                $debit = (float) $tag->debit_total;

                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'credit' => $credit,
                    'debit' => $debit,
                    'net' => $credit - $debit,
                ];
            })
            ->sortByDesc(fn (array $tag) => $tag['credit'] + $tag['debit'])
            ->values();

        $totals = BankTransaction::query()
            ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credit_total")
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as debit_total")
            ->whereHas('account', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('tags', function (Builder $query) use ($user) {
                $query->where('tags.user_id', $user->id);
            })
            ->first();

        return [
            'totals' => [
                'credit' => (float) ($totals->credit_total ?? 0),
                'debit' => (float) ($totals->debit_total ?? 0),
            ],
            'breakdown' => $breakdown->all(),
        ];
    }

    public function edit(Request $request, BankAccount $account): ModalResponse
    {
        $this->ensureOwnsAccount($request->user(), $account);

        return Inertia::modal('accounts/modals/EditBankAccount', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'institution' => $account->institution,
                'account_type' => $account->account_type,
                'account_number' => $account->account_number,
                'currency' => $account->currency,
            ],
            'accountTypes' => $this->accountTypeOptions(),
        ])->baseRoute('accounts.index');
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $account): RedirectResponse
    {
        $this->ensureOwnsAccount($request->user(), $account);

        $account->update($request->validated());

        return back_from_modal()->with('success', 'Conta atualizada com sucesso.');
    }

    protected function ensureOwnsAccount(?User $user, BankAccount $account): void
    {
        abort_if(! $user, 403);
        abort_if($account->user_id !== $user->id, 403);
    }

    protected function accountTypeOptions(): array
    {
        return [
            ['value' => 'checking', 'label' => 'Conta corrente'],
            ['value' => 'savings', 'label' => 'Conta poupança'],
            ['value' => 'credit', 'label' => 'Cartão de crédito'],
            ['value' => 'investment', 'label' => 'Investimentos'],
            ['value' => 'business', 'label' => 'Conta empresarial'],
        ];
    }
}
