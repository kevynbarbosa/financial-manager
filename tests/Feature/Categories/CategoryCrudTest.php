<?php

use App\Models\TransactionCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists and creates categories', function () {
    $user = User::factory()->create();

    actingAs($user);

    $this->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page->component('categories/Index'));

    $response = $this->post(route('categories.store'), [
        'name' => 'Alimentação',
        'icon' => 'shopping-bag',
        'color' => '#22c55e',
    ]);

    $response->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('transaction_categories', [
        'user_id' => $user->id,
        'name' => 'Alimentação',
    ]);
});

it('updates and deletes categories for the owner only', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->for($user)->create([
        'name' => 'Transporte',
    ]);

    actingAs($user);

    $this->put(route('categories.update', $category), [
        'name' => 'Mobilidade',
        'icon' => 'car',
        'color' => '#f97316',
    ])->assertRedirect(route('categories.index'));

    $category->refresh();
    expect($category->name)->toBe('Mobilidade');

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('transaction_categories', ['id' => $category->id]);
});

it('prevents other users from touching categories they do not own', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = TransactionCategory::factory()->for($owner)->create();

    actingAs($intruder);

    $this->put(route('categories.update', $category), [
        'name' => 'Hacking',
        'icon' => 'wallet',
        'color' => '#000000',
    ])->assertForbidden();

    $this->delete(route('categories.destroy', $category))->assertForbidden();
});
