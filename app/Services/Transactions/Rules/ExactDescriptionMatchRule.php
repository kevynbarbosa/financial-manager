<?php

namespace App\Services\Transactions\Rules;

use App\Models\BankTransaction;
use App\Models\User;
use App\Services\Transactions\DescriptionNormalizer;

class ExactDescriptionMatchRule implements TransactionCategoryRuleInterface
{
    public function suggest(User $user, string $description): ?array
    {
        $normalized = DescriptionNormalizer::normalize($description);

        $match = BankTransaction::query()
            ->select('transaction_categories.id as id', 'transaction_categories.name as name')
            ->join('bank_accounts', 'bank_transactions.bank_account_id', '=', 'bank_accounts.id')
            ->join('transaction_categories', 'bank_transactions.transaction_category_id', '=', 'transaction_categories.id')
            ->where('bank_accounts.user_id', $user->id)
            ->whereRaw("LOWER(REGEXP_REPLACE(TRIM(bank_transactions.description), '\\\\s+', ' ')) = ?", [$normalized])
            ->orderByDesc('bank_transactions.occurred_at')
            ->orderByDesc('bank_transactions.id')
            ->first();

        if (! $match) {
            return null;
        }

        return [
            'id' => (int) $match->id,
            'name' => $match->name,
        ];
    }
}
