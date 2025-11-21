<?php

namespace App\Services\Transactions\Rules;

use App\Models\TransactionCategory;
use App\Models\User;

class IfoodCategoryRule implements TransactionCategoryRuleInterface
{
    public function suggest(User $user, string $description): ?array
    {
        $normalized = $this->normalizeDescription($description);

        if (! str_starts_with($normalized, 'ifd*')) {
            return null;
        }

        $category = TransactionCategory::query()
            ->where('user_id', $user->id)
            ->whereRaw('LOWER(name) = ?', ['ifood'])
            ->first();

        if (! $category) {
            return null;
        }

        return [
            'id' => (int) $category->id,
            'name' => $category->name,
        ];
    }

    private function normalizeDescription(string $description): string
    {
        $normalized = preg_replace('/\s+/', ' ', strtolower(trim($description)));

        return $normalized ?? '';
    }
}
