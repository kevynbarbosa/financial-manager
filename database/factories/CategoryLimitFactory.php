<?php

namespace Database\Factories;

use App\Models\CategoryLimit;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryLimit>
 */
class CategoryLimitFactory extends Factory
{
    protected $model = CategoryLimit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_category_id' => TransactionCategory::factory(),
            'monthly_limit' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
