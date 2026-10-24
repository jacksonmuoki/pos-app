<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        return view('inventory.adjustments.index', [
            'adjustments' => StockAdjustment::with('product', 'user')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('inventory.adjustments.create', [
            'products' => Product::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type'       => ['required', 'in:add,remove,set'],
            'quantity'   => ['required', 'integer', 'min:0'],
            'reason'     => ['required', 'in:damaged,expired,lost,found,recount,other'],
            'notes'      => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $before  = $product->stock;
            $after   = match ($data['type']) {
                'add'    => $before + $data['quantity'],
                'remove' => $before - $data['quantity'],
                'set'    => $data['quantity'],
            };

            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot go below zero.']);
            }

            $product->update(['stock' => $after]);

            StockAdjustment::create([
                'product_id'   => $product->id,
                'user_id'      => auth()->id(),
                'change'       => $after - $before,
                'stock_before' => $before,
                'stock_after'  => $after,
                'reason'       => $data['reason'],
                'notes'        => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('adjustments.index')->with('status', 'Stock adjusted.');
    }
}