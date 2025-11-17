<?php

namespace Database\Factories;

use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    public function definition(): array
    {
        $icons = ['shopping-bag', 'wallet', 'credit-card', 'car'];
        $colors = ['#22c55e', '#ef4444', '#3b82f6', '#f97316'];

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->unique()->word(),
            'icon' => $this->faker->randomElement($icons),
            'color' => $this->faker->randomElement($colors),
        ];
    }
}
