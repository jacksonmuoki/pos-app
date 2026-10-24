<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) =>
                $q->where('name', 'like', "%$s%")
                  ->orWhere('sku', 'like', "%$s%")
                  ->orWhere('barcode', 'like', "%$s%")))
            ->when($request->filter === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'reorder_level'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.stock', compact('products'));
    }
}