<?php

namespace App\Services\Transactions\Rules;

use App\Models\User;

interface TransactionCategoryRuleInterface
{
    /**
     * @return array{id: int, name: string}|null
     */
    public function suggest(User $user, string $description): ?array;
}
