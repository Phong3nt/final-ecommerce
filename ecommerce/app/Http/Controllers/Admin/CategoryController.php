<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('parent')->orderBy('name')->paginate(20);
        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents = Category::orderBy('name')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        // Exclude self and own children from parent options to prevent cycles
        $parents = Category::where('id', '!=', $category->id)
            ->where(function ($q) use ($category) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', '!=', $category->id);
            })
            ->orderBy('name')->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category->id])],
        ]);

        $category->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Disassociate products from this category before deleting
        Product::where('category_id', $category->id)->update(['category_id' => null]);

        // Detach children (set their parent_id to null)
        Category::where('parent_id', $category->id)->update(['parent_id' => null]);

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
