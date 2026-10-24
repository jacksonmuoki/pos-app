<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('inventory.purchases.index', [
            'purchases' => Purchase::with('supplier')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('inventory.purchases.create', [
            'suppliers' => Supplier::where('active', true)->orderBy('name')->get(),
            'products'  => Product::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'        => ['required', 'exists:suppliers,id'],
            'purchase_date'      => ['required', 'date'],
            'notes'              => ['nullable', 'string', 'max:255'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'reference'     => 'PO-' . now()->format('ymdHis') . random_int(10, 99),
                'supplier_id'   => $data['supplier_id'],
                'user_id'       => auth()->id(),
                'purchase_date' => $data['purchase_date'],
                'notes'         => $data['notes'] ?? null,
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $line = round($row['quantity'] * $row['unit_cost'], 2);

                $purchase->items()->create([
                    'product_id' => $row['product_id'],
                    'quantity'   => $row['quantity'],
                    'unit_cost'  => $row['unit_cost'],
                    'line_total' => $line,
                ]);

                $product = Product::lockForUpdate()->find($row['product_id']);
                $product->update([
                    'stock' => $product->stock + $row['quantity'],
                    'cost'  => $row['unit_cost'],
                ]);

                $total += $line;
            }

            $purchase->update(['total' => $total]);

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)->with('status', 'Purchase received, stock updated.');
    }

    public function show(Purchase $purchase)
    {
        return view('inventory.purchases.show', [
            'purchase' => $purchase->load('supplier', 'items.product'),
        ]);
    }
}