<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::query()
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
            'type' => 'brands',
            'title' => 'Brands',
            'description' => 'Manage the brands represented in your store.',
            'items' => $brands,
            'total' => Brand::count(),
            'activeCount' => Brand::where('is_active', true)->count(),
            'createRoute' => 'catalogue.brands.create',
        ]);
    }

    public function create()
    {
        return view('tenant.catalogue.form', [
            'type' => 'brands',
            'title' => 'Add brand',
            'item' => new Brand(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateBrand($request);

        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')
                ->store('brands', 'public');
        }

        Brand::create($data);

        return redirect()
            ->route('catalogue.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('tenant.catalogue.form', [
            'type' => 'brands',
            'title' => 'Edit brand',
            'item' => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validateBrand($request);

        $data['slug'] = $this->uniqueSlug(
            $data['name'],
            $brand->id
        );

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }

            $data['logo_path'] = $request->file('logo')
                ->store('brands', 'public');
        }

        $brand->update($data);

        return redirect()
            ->route('catalogue.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return back()->with(
                'error',
                'This brand is assigned to products. Reassign those products before deleting it.'
            );
        }

        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }

    private function validateBrand(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        unset($data['logo']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $counter = 1;

        while (
            Brand::withTrashed()
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