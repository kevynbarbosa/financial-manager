<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Seed default transaction categories for every user.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $categories = [
            [
                'name' => 'Custos Fixos',
                'icon' => 'home',
                'color' => '#0ea5e9',
            ],
            [
                'name' => 'iFood',
                'icon' => 'coffee',
                'color' => '#f97316',
            ],
            [
                'name' => 'Mercado',
                'icon' => 'shopping-bag',
                'color' => '#14b8a6',
            ],
            [
                'name' => 'Lazer & Viagens',
                'icon' => 'gift',
                'color' => '#a855f7',
            ],
            [
                'name' => 'Transporte',
                'icon' => 'car',
                'color' => '#facc15',
            ],
            [
                'name' => 'Saúde & Bem-estar',
                'icon' => 'dumbbell',
                'color' => '#ec4899',
            ],
            [
                'name' => 'Poupança',
                'icon' => 'piggy-bank',
                'color' => '#22d3ee',
            ],
        ];

        $users->each(function (User $user) use ($categories) {
            foreach ($categories as $category) {
                TransactionCategory::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $category['name'],
                    ],
                    [
                        'icon' => $category['icon'],
                        'color' => $category['color'],
                    ],
                );
            }
        });
    }
}
