@extends('inventory.layout')
@section('title', 'New Purchase')
@section('content')
<h2>New Purchase</h2>
<form method="POST" action="{{ route('purchases.store') }}">
    @csrf
    <div class="card">
        <select name="supplier_id" required>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
        </select>
        <input name="reference" placeholder="Invoice / reference" value="{{ old('reference') }}">
        <input type="date" name="purchased_at" value="{{ old('purchased_at', now()->toDateString()) }}" required>
    </div>

    <table id="rows">
        <tr><th>Product</th><th>Qty</th><th>Unit cost</th></tr>
        <tr>
            <td>
                <select name="items[0][product_id]">
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[0][quantity]" min="1" value="1"></td>
            <td><input type="number" step="0.01" min="0" name="items[0][unit_cost]" required></td>
        </tr>
    </table>
    <p>
        <button type="button" class="secondary" onclick="addRow()">+ Add line</button>
        <button>Save purchase</button>
    </p>
</form>

<script>
let i = 1;
function addRow() {
    const t = document.getElementById('rows');
    const r = t.rows[1].cloneNode(true);
    r.querySelectorAll('select,input').forEach(el => {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
        if (el.tagName === 'INPUT') el.value = el.name.includes('quantity') ? 1 : '';
    });
    t.appendChild(r);
    i++;
}
</script>
@endsection