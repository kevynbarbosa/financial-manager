<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        [$startDate, $endDate] = $this->resolveDateFilters($request);

        $categorySpending = $this->categorySpendingReport(
            userId: $user->id,
            startDate: $startDate,
            endDate: $endDate,
        );

        return Inertia::render('Dashboard', [
            'categorySpending' => $categorySpending,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * @return array{total: float, categories: array<int, array{id: int|null, name: string, icon: string|null, color: string|null, total: float, percentage: float}>}
     */
    private function categorySpendingReport(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $spending = BankTransaction::query()
            ->selectRaw('COALESCE(transaction_categories.id, 0) as category_id')
            ->selectRaw("COALESCE(transaction_categories.name, bank_transactions.category, 'Sem categoria') as category_name")
            ->selectRaw("COALESCE(transaction_categories.icon, 'wallet') as category_icon")
            ->selectRaw("COALESCE(transaction_categories.color, '#94a3b8') as category_color")
            ->selectRaw('SUM(bank_transactions.amount) as total_spent')
            ->join('bank_accounts', 'bank_accounts.id', '=', 'bank_transactions.bank_account_id')
            ->leftJoin('transaction_categories', function ($join) use ($userId) {
                $join->on('transaction_categories.id', '=', 'bank_transactions.transaction_category_id')
                    ->where('transaction_categories.user_id', '=', $userId);
            })
            ->where('bank_accounts.user_id', $userId)
            ->where('bank_transactions.type', 'debit')
            ->where('bank_transactions.is_transfer', false)
            ->whereBetween('bank_transactions.occurred_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->groupBy(
                'bank_transactions.transaction_category_id',
                'transaction_categories.id',
                'transaction_categories.name',
                'transaction_categories.icon',
                'transaction_categories.color',
                'bank_transactions.category'
            )
            ->orderByDesc('total_spent')
            ->get();

        $totalSpent = round($spending->sum(fn ($row) => abs((float) $row->total_spent)), 2);

        $categories = $spending->map(function ($row) use ($totalSpent) {
            $total = round(abs((float) $row->total_spent), 2);

            return [
                'id' => $row->category_id ? (int) $row->category_id : null,
                'name' => $row->category_name,
                'icon' => $row->category_icon,
                'color' => $row->category_color,
                'total' => $total,
                'percentage' => $totalSpent > 0 ? round(($total / $totalSpent) * 100, 2) : 0.0,
            ];
        })->values()->all();

        return [
            'total' => $totalSpent,
            'categories' => $categories,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateFilters(Request $request): array
    {
        $defaultEnd = now()->endOfDay();
        $defaultStart = now()->copy()->subDays(30)->startOfDay();

        $start = $this->parseDate($request->input('start_date'), $defaultStart)->startOfDay();
        $end = $this->parseDate($request->input('end_date'), $defaultEnd)->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function parseDate(?string $value, Carbon $default): Carbon
    {
        if (! $value) {
            return $default->copy();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable $exception) {
            return $default->copy();
        }
    }
}
