<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('inventory.suppliers', ['suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Supplier::create($request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('status', 'Supplier added.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['active' => ! $supplier->active]);

        return back()->with('status', 'Supplier updated.');
    }
}