<?php

namespace App\Services\Transactions;

use App\Models\User;
use App\Services\Transactions\Rules\ExactDescriptionMatchRule;
use App\Services\Transactions\Rules\IfoodCategoryRule;
use App\Services\Transactions\Rules\TransactionCategoryRuleInterface;

class TransactionCategorizer
{
    /**
     * @var array<int, TransactionCategoryRuleInterface>
     */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new IfoodCategoryRule(),
            new ExactDescriptionMatchRule(),
        ];
    }

    /**
     * @param  User  $user
     * @param  string|null  $description
     * @return array{id: int, name: string}|null
     */
    public function suggestCategory(User $user, ?string $description): ?array
    {
        if (! $description) {
            return null;
        }

        foreach ($this->rules as $rule) {
            $suggestion = $rule->suggest($user, $description);

            if ($suggestion) {
                return $suggestion;
            }
        }

        return null;
    }
}
