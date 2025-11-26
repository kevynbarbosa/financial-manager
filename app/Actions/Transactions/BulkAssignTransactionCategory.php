<?php

namespace App\Actions\Transactions;

use App\Http\Requests\BulkAssignTransactionCategoryRequest;
use App\Models\BankTransaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\Transactions\DescriptionNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class BulkAssignTransactionCategory
{
    public function __invoke(BulkAssignTransactionCategoryRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $overwrite = (bool) ($validated['overwrite_existing'] ?? false);

        $category = TransactionCategory::query()
            ->where('user_id', $user->id)
            ->findOrFail($validated['category_id']);

        $transactions = $this->buildTargetTransactions($user, $validated, $overwrite);

        $updatedCount = $transactions->update([
            'transaction_category_id' => $category->id,
            'category' => $category->name,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', $this->buildBulkAssignMessage($updatedCount));
    }

    private function buildTargetTransactions(User $user, array $validated, bool $overwrite): Builder
    {
        $transactions = BankTransaction::query()
            ->whereHas('account', fn (Builder $query) => $query->where('user_id', $user->id));

        if (! $overwrite) {
            $transactions->whereNull('transaction_category_id');
        }

        if ($validated['match_type'] === 'exact') {
            $transactions->whereRaw(
                "LOWER(REGEXP_REPLACE(TRIM(description), '\\s+', ' ', 'g')) = ?",
                [DescriptionNormalizer::normalize($validated['term'])]
            );
        } else {
            $likeTerm = '%'.addcslashes(mb_strtolower(trim($validated['term'])), '%_').'%';
            $transactions->whereRaw('LOWER(description) LIKE ?', [$likeTerm]);
        }

        return $transactions;
    }

    private function buildBulkAssignMessage(int $count): string
    {
        if ($count === 0) {
            return 'Nenhuma transação foi atualizada para a categoria selecionada.';
        }

        if ($count === 1) {
            return '1 transação foi atualizada para a categoria selecionada.';
        }

        return sprintf('%d transações foram atualizadas para a categoria selecionada.', $count);
    }
}
