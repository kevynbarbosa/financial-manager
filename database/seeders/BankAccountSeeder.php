<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Finance Manager',
            'email' => 'finance@example.com',
        ]);

        $accounts = [
            [
                'name' => 'Conta Corrente - Nubank',
                'institution' => 'Nubank',
                'account_type' => 'checking',
                'balance' => 12540.74,
            ],
            [
                'name' => 'Conta PJ - Itaú',
                'institution' => 'Itaú Empresas',
                'account_type' => 'business',
                'balance' => 30210.90,
            ],
            [
                'name' => 'Poupança - Caixa',
                'institution' => 'Caixa Econômica',
                'account_type' => 'savings',
                'balance' => 18200.00,
            ],
        ];

        foreach ($accounts as $accountData) {
            BankAccount::factory()
                ->for($user)
                ->state($accountData)
                ->has(
                    BankTransaction::factory()
                        ->count(20)
                        ->state(fn () => [
                            'occurred_at' => now()->subDays(random_int(0, 45)),
                        ]),
                    'transactions'
                )
                ->create();
        }
    }
}
