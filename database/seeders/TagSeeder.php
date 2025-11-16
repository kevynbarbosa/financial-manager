<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Finance Manager',
                'email' => 'finance@example.com',
            ]);
        }

        $defaultTags = [
            'Essenciais',
            'Investimentos',
            'Lazer',
            'Impostos',
            'Transferências',
            'Receitas',
        ];

        User::all()->each(function (User $user) use ($defaultTags) {
            foreach ($defaultTags as $tagName) {
                Tag::firstOrCreate([
                    'user_id' => $user->id,
                    'name' => $tagName,
                ]);
            }
        });
    }
}
