@extends('inventory.layout')
@section('title', 'Stock Management')
@section('content')
<h2>Stock Management</h2>
<table>
    <tr><th>Product</th><th>SKU</th><th>Stock</th><th>Reorder at</th><th>Status</th></tr>
    @forelse($products as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td>{{ $p->sku }}</td>
            <td>{{ $p->stock }}</td>
            <td>{{ $p->reorder_level }}</td>
            <td>@if($p->stock <= $p->reorder_level)<span class="low">Low</span>@else OK @endif</td>
        </tr>
    @empty
        <tr><td colspan="5">No products yet.</td></tr>
    @endforelse
</table>
@endsection