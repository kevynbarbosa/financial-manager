<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTransactionTagsRequest;
use App\Http\Requests\UpdateTransactionCategoryRequest;
use App\Models\BankTransaction;
use App\Models\Tag;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InertiaUI\Modal\Modal as ModalResponse;

use function InertiaUI\Modal\back_from_modal;

class BankTransactionTagController extends Controller
{
    public function edit(Request $request, BankTransaction $transaction): ModalResponse
    {
        $user = $request->user();
        $this->ensureOwnsTransaction($transaction, $user);

        $transaction->loadMissing([
            'account:id,name,institution,user_id',
            'categoryRelation:id,name,icon,color',
            'tags:id,name',
        ]);

        $availableTags = Tag::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])
            ->values()
            ->all();

        $categoryOptions = TransactionCategory::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color'])
            ->map(fn (TransactionCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
            ])
            ->values()
            ->all();

        return Inertia::modal('accounts/modals/ManageTransactionTags', [
            'transaction' => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'amount' => (float) $transaction->amount,
                'type' => $transaction->type,
                'occurred_at' => optional($transaction->occurred_at)->toDateTimeString(),
                'account' => [
                    'id' => $transaction->account->id,
                    'name' => $transaction->account->name,
                    'institution' => $transaction->account->institution,
                ],
                'category' => $transaction->categoryRelation ? [
                    'id' => $transaction->categoryRelation->id,
                    'name' => $transaction->categoryRelation->name,
                    'icon' => $transaction->categoryRelation->icon,
                    'color' => $transaction->categoryRelation->color,
                ] : null,
                'tags' => $transaction->tags->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])->values()->all(),
            ],
            'availableTags' => $availableTags,
            'categoryOptions' => $categoryOptions,
        ])->baseRoute('accounts.index');
    }

    public function update(UpdateTransactionTagsRequest $request, BankTransaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->ensureOwnsTransaction($transaction, $user);

        $validated = $request->validated();

        $categoryId = $validated['category_id'] ?? null;
        $categoryName = null;

        if ($categoryId) {
            $category = TransactionCategory::query()
                ->where('user_id', $user->id)
                ->findOrFail($categoryId);

            $categoryName = $category->name;
        }

        $transaction->update([
            'description' => $validated['description'],
            'transaction_category_id' => $categoryId,
            'category' => $categoryName,
        ]);

        $tagNames = collect($validated['tags'] ?? [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique();

        $tagIds = $tagNames->map(function (string $name) use ($user) {
            return Tag::firstOrCreate([
                'user_id' => $user->id,
                'name' => $name,
            ])->id;
        });

        $transaction->tags()->sync($tagIds->all());

        return back_from_modal()->with('success', 'Transação atualizada com sucesso.');
    }

    public function updateCategory(UpdateTransactionCategoryRequest $request, BankTransaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->ensureOwnsTransaction($transaction, $user);

        $categoryId = $request->validated('category_id');
        $categoryName = null;

        if ($categoryId) {
            $category = TransactionCategory::query()
                ->where('user_id', $user->id)
                ->findOrFail($categoryId);

            $categoryName = $category->name;
        }

        $transaction->update([
            'transaction_category_id' => $categoryId,
            'category' => $categoryName,
        ]);

        return back()->with('success', 'Categoria atualizada.');
    }

    protected function ensureOwnsTransaction(BankTransaction $transaction, ?User $user): void
    {
        abort_if(
            ! $user,
            403,
            'Usuário não autenticado.'
        );

        $transaction->loadMissing('account:id,user_id');

        abort_if(
            $transaction->account?->user_id !== $user->id,
            403,
            'Operação não permitida.'
        );
    }
}
