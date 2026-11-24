
@extends('super.layout')

@section('title', 'Create Plan')
@section('breadcrumb', 'Create Plan')

@section('content')

<div class="page-header">
    <h1>Create Pricing Plan</h1>
    <p>Configure a new subscription plan for your companies.</p>
</div>

<div class="card">
    <div class="card-body">

        <form action="{{ route('super.plans.store') }}" method="POST">
            @csrf

            @include('super.plans._form', [
                'buttonText' => 'Create Plan'
            ])

        </form>

    </div>
</div>

@endsection
