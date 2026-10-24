@extends('inventory.layout')
@section('title', 'Suppliers')
@section('content')
<h2>Suppliers</h2>
<div class="card">
    <form class="inline" method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        <input name="name" placeholder="Name" value="{{ old('name') }}" required>
        <input name="phone" placeholder="Phone" value="{{ old('phone') }}">
        <input name="email" placeholder="Email" value="{{ old('email') }}">
        <button>Add supplier</button>
    </form>
</div>

<table>
    <tr><th>Name</th><th>Phone</th><th>Email</th></tr>
    @forelse($suppliers as $s)
        <tr><td>{{ $s->name }}</td><td>{{ $s->phone }}</td><td>{{ $s->email }}</td></tr>
    @empty
        <tr><td colspan="3">No suppliers yet.</td></tr>
    @endforelse
</table>
@endsection