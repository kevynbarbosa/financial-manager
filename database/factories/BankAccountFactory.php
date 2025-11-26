<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        $institutions = ['Nubank', 'Itaú', 'Caixa', 'Bradesco', 'Inter'];
        $accountTypes = ['checking', 'savings', 'business'];

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true).' Account',
            'institution' => $this->faker->randomElement($institutions),
            'account_type' => $this->faker->randomElement($accountTypes),
            'account_number' => $this->faker->bankAccountNumber(),
            'balance' => $this->faker->randomFloat(2, 500, 50000),
            'currency' => 'BRL',
            'metadata' => [
                'import_source' => 'factory',
            ],
        ];
    }
}
