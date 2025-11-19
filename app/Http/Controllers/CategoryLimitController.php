<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryLimitRequest;
use App\Http\Requests\UpdateCategoryLimitRequest;
use App\Models\BankTransaction;
use App\Models\CategoryLimit;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CategoryLimitController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $categories = TransactionCategory::query()
            ->where('user_id', $user->id)
            ->with([
                'limit' => fn ($relation) => $relation->where('user_id', $user->id),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color']);

        $spentByCategory = BankTransaction::query()
            ->selectRaw('transaction_category_id, ABS(SUM(amount)) as total_spent')
            ->whereHas('account', fn (Builder $query) => $query->where('user_id', $user->id))
            ->where('type', 'debit')
            ->where('is_transfer', false)
            ->whereNotNull('transaction_category_id')
            ->whereBetween('occurred_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->groupBy('transaction_category_id')
            ->pluck('total_spent', 'transaction_category_id');

        $categoryPayload = $categories->map(function (TransactionCategory $category) use ($spentByCategory) {
            $limitAmount = $category->limit?->monthly_limit !== null ? (float) $category->limit->monthly_limit : null;
            $spent = (float) ($spentByCategory[$category->id] ?? 0);
            $progress = $limitAmount && $limitAmount > 0
                ? min(100, round(($spent / $limitAmount) * 100, 1))
                : null;
            $remaining = $limitAmount !== null ? $limitAmount - $spent : null;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'limit' => $category->limit
                    ? [
                        'id' => $category->limit->id,
                        'monthly_limit' => (float) $category->limit->monthly_limit,
                    ]
                    : null,
                'spent' => $spent,
                'remaining' => $remaining,
                'progress' => $progress,
            ];
        })->sortByDesc('spent')->values();

        return Inertia::render('category-limits/Index', [
            'categories' => $categoryPayload,
            'period' => [
                'label' => sprintf('%s %s', ucfirst($periodStart->monthName), $periodStart->year),
                'start' => $periodStart->toDateString(),
                'end' => $periodEnd->toDateString(),
            ],
        ]);
    }

    public function store(StoreCategoryLimitRequest $request): RedirectResponse
    {
        $user = $request->user();

        CategoryLimit::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'transaction_category_id' => (int) $request->input('transaction_category_id'),
            ],
            [
                'monthly_limit' => $request->input('monthly_limit'),
            ],
        );

        return redirect()
            ->route('category-limits.index')
            ->with('success', 'Limite salvo com sucesso.');
    }

    public function update(UpdateCategoryLimitRequest $request, CategoryLimit $categoryLimit): RedirectResponse
    {
        $this->authorizeLimit($request->user(), $categoryLimit);

        $categoryLimit->update([
            'monthly_limit' => $request->input('monthly_limit'),
        ]);

        return redirect()
            ->route('category-limits.index')
            ->with('success', 'Limite atualizado.');
    }

    public function destroy(Request $request, CategoryLimit $categoryLimit): RedirectResponse
    {
        $this->authorizeLimit($request->user(), $categoryLimit);

        $categoryLimit->delete();

        return redirect()
            ->route('category-limits.index')
            ->with('success', 'Limite removido.');
    }

    protected function authorizeLimit(?\App\Models\User $user, CategoryLimit $categoryLimit): void
    {
        abort_if(! $user, 403);
        abort_if($categoryLimit->user_id !== $user->id, 403);
    }
}
