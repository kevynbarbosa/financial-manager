<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['credit', 'debit']);
        $amount = $this->faker->randomFloat(2, 50, 5000);

        return [
            'bank_account_id' => BankAccount::factory(),
            'description' => $this->faker->sentence(3),
            'amount' => $amount,
            'type' => $type,
            'occurred_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'category' => $this->faker->randomElement(['Salário', 'Transferência', 'Despesa Fixa', 'Investimento']),
            'external_id' => $this->faker->uuid(),
            'metadata' => [
                'source' => 'factory',
            ],
        ];
    }

    public function credit(): self
    {
        return $this->state(fn () => ['type' => 'credit']);
    }

    public function debit(): self
    {
        return $this->state(fn () => ['type' => 'debit']);
    }
}
