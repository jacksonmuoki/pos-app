
@extends('super.layout')

@section('title', 'Edit Plan')
@section('breadcrumb', 'Edit Plan')

@section('content')

<div class="page-header">
    <h1>Edit Pricing Plan</h1>
    <p>Update {{ $plan->name }} pricing, limits and features.</p>
</div>

<div class="card">
    <div class="card-body">

        <form action="{{ route('super.plans.update', $plan) }}" method="POST">
            @csrf
            @method('PUT')

            @include('super.plans._form', [
                'plan' => $plan,
                'buttonText' => 'Save Changes'
            ])

        </form>

    </div>
</div>

@endsection
