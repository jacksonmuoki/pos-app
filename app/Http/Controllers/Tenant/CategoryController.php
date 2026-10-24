<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('parent')
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where(
                    'is_active',
                    $request->status === 'active'
                );
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('tenant.catalogue.index', [
            'type' => 'categories',
            'title' => 'Categories',
            'description' => 'Organise your products into categories.',
            'items' => $categories,
            'total' => Category::count(),
            'activeCount' => Category::where('is_active', true)->count(),
            'createRoute' => 'catalogue.categories.create',
        ]);
    }

    public function create()
    {
        return view('tenant.catalogue.form', [
            'type' => 'categories',
            'title' => 'Add category',
            'item' => new Category(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);

        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('categories', 'public');
        }

        Category::create($data);

        return redirect()
            ->route('catalogue.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('tenant.catalogue.form', [
            'type' => 'categories',
            'title' => 'Edit category',
            'item' => $category,
            'categories' => Category::where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateCategory($request);

        $data['slug'] = $this->uniqueSlug(
            $data['name'],
            $category->id
        );

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $data['image_path'] = $request->file('image')
                ->store('categories', 'public');
        }

        $category->update($data);

        return redirect()
            ->route('catalogue.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with(
                'error',
                'This category has products. Reassign those products before deleting it.'
            );
        }

        if ($category->children()->exists()) {
            return back()->with(
                'error',
                'This category has subcategories. Reassign them before deleting it.'
            );
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    private function validateCategory(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        unset($data['image']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 1;

        while (
            Category::withTrashed()
                ->where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($query) => $query->where('id', '!=', $ignoreId)
                )
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}