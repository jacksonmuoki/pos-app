<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'brand'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($q) =>
                $q->where('category_id', $request->category)
            )
            ->when($request->filled('brand'), fn ($q) =>
                $q->where('brand_id', $request->brand)
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('active', $request->status === 'active')
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('tenant.catalogue.index', [
            'type' => 'products',
            'title' => 'Products',
            'description' => 'Manage your products, prices and stock levels.',
            'items' => $products,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'total' => Product::count(),
            'activeCount' => Product::where('active', true)->count(),
            'createRoute' => 'catalogue.products.create',
        ]);
    }

    public function create()
    {
        return view('tenant.catalogue.form', [
            'type' => 'products',
            'title' => 'Add product',
            'item' => new Product(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store('products', 'public');
        }

        Product::create($data);

        return redirect()
            ->route('catalogue.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('tenant.catalogue.form', [
            'type' => 'products',
            'title' => 'Edit product',
            'item' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')
                ->store('products', 'public');
        }

        $product->update($data);

        return redirect()
            ->route('catalogue.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if (
            Schema::hasTable('order_items') &&
            DB::table('order_items')
                ->where('product_id', $product->id)
                ->exists()
        ) {
            return back()->with(
                'error',
                'This product is linked to sales and cannot be deleted.'
            );
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    private function validateProduct(
        Request $request,
        ?Product $product = null
    ): array {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],

            'barcode' => ['nullable', 'string', 'max:100'],

            'description' => ['nullable', 'string'],

            'price' => ['required', 'numeric', 'min:0'],

            'cost' => ['nullable', 'numeric', 'min:0'],

            'wholesale_price' => ['nullable', 'numeric', 'min:0'],

            'stock' => ['nullable', 'integer', 'min:0'],

            'reorder_level' => ['nullable', 'integer', 'min:0'],

            'unit' => ['required', 'string', 'max:40'],

            'product_type' => [
                'required',
                Rule::in(['goods', 'service']),
            ],

            'image' => ['nullable', 'image', 'max:2048'],

            'track_stock' => ['nullable', 'boolean'],

            'is_taxable' => ['nullable', 'boolean'],

            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'active' => ['nullable', 'boolean'],
        ]);

        unset($data['image']);

        $data['cost'] = $data['cost'] ?? 0;
        $data['stock'] = $data['stock'] ?? 0;
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['track_stock'] = $request->boolean('track_stock');
        $data['is_taxable'] = $request->boolean('is_taxable');
        $data['active'] = $request->boolean('active');

        return $data;
    }
}