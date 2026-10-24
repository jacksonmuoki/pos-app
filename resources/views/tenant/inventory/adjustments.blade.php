@extends('inventory.layout')
@section('title', 'Stock Adjustments')
@section('content')
<h2>Stock Adjustments</h2>
<div class="card">
    <form class="inline" method="POST" action="{{ route('adjustments.store') }}">
        @csrf
        <select name="product_id" required>
            @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }} (stock {{ $p->stock }})</option>
            @endforeach
        </select>
        <input type="number" name="change" placeholder="+5 or -2" required>
        <select name="reason">
            @foreach(['recount','damaged','expired','theft','returned','other'] as $r)
                <option value="{{ $r }}">{{ ucfirst($r) }}</option>
            @endforeach
        </select>
        <input name="note" placeholder="Note (optional)">
        <button>Save</button>
    </form>
</div>

<table>
    <tr><th>Date</th><th>Product</th><th>Change</th><th>Before → After</th><th>Reason</th><th>By</th></tr>
    @forelse($adjustments as $a)
        <tr>
            <td>{{ $a->created_at->format('d M Y H:i') }}</td>
            <td>{{ $a->product->name }}</td>
            <td>{{ $a->change > 0 ? '+' : '' }}{{ $a->change }}</td>
            <td>{{ $a->stock_before }} → {{ $a->stock_after }}</td>
            <td>{{ $a->reason }}</td>
            <td>{{ $a->user->name }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No adjustments yet.</td></tr>
    @endforelse
</table>
@endsection