<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal as ModalResponse;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $categories = TransactionCategory::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get(['id', 'name', 'icon', 'color']);

        return Inertia::render('categories/Index', [
            'categories' => $categories,
            'iconOptions' => $this->iconOptions(),
            'colorOptions' => $this->colorOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->transactionCategories()->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Categoria criada com sucesso.');
    }

    public function update(UpdateCategoryRequest $request, TransactionCategory $category): RedirectResponse
    {
        $this->authorizeCategory($request->user(), $category);

        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Request $request, TransactionCategory $category): RedirectResponse
    {
        $this->authorizeCategory($request->user(), $category);

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoria removida.');
    }

    protected function authorizeCategory($user, TransactionCategory $category): void
    {
        abort_if(! $user, 403);
        abort_if($category->user_id !== $user->id, 403);
    }

    protected function iconOptions(): array
    {
        return [
            'wallet',
            'credit-card',
            'shopping-bag',
            'store',
            'piggy-bank',
            'car',
            'home',
            'gift',
            'coffee',
            'dumbbell',
        ];
    }

    protected function colorOptions(): array
    {
        return [
            '#0ea5e9',
            '#22c55e',
            '#ef4444',
            '#f97316',
            '#a855f7',
            '#14b8a6',
            '#6366f1',
            '#f43f5e',
            '#94a3b8',
            '#eab308',
            '#fb7185',
            '#2dd4bf',
            '#38bdf8',
            '#8b5cf6',
            '#facc15',
            '#34d399',
            '#f472b6',
            '#c084fc',
            '#7dd3fc',
            '#ef4444',
            '#0f172a',
            '#94a3b8',
        ];
    }

    public function create(Request $request): ModalResponse
    {
        return Inertia::modal('categories/modals/CreateCategory', [
            'iconOptions' => $this->iconOptions(),
            'colorOptions' => $this->colorOptions(),
        ])->baseRoute('categories.index');
    }

    public function edit(Request $request, TransactionCategory $category): ModalResponse
    {
        $this->authorizeCategory($request->user(), $category);

        return Inertia::modal('categories/modals/EditCategory', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
            ],
            'iconOptions' => $this->iconOptions(),
            'colorOptions' => $this->colorOptions(),
        ])->baseRoute('categories.index');
    }
}
