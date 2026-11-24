
@extends('super.layout')

@section('title', 'Plans & Pricing')
@section('breadcrumb', 'Plans & Pricing')

@section('content')

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:25px;">
    <div>
        <h1>Plans & Pricing</h1>
        <p>Manage subscription plans, pricing and available features.</p>
    </div>

    <a href="{{ route('super.plans.create') }}"
       class="btn btn-primary">
        + Create Plan
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        Please check the form for errors.
    </div>
@endif

<div class="card">
    <div class="card-body">

        <div style="overflow-x:auto;">
            <table class="table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Monthly</th>
                        <th>Quarterly</th>
                        <th>Annual</th>
                        <th>Limits</th>
                        <th>Features</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <strong>{{ $plan->name }}</strong>

                                @if($plan->is_popular)
                                    <div>
                                        <span class="badge badge-primary">Popular</span>
                                    </div>
                                @endif

                                <small style="display:block;color:#777;">
                                    {{ $plan->slug }}
                                </small>
                            </td>

                            <td>
                                {{ $plan->currency }}
                                {{ number_format((float)$plan->monthly_price, 2) }}
                            </td>

                            <td>
                                {{ $plan->currency }}
                                {{ number_format((float)$plan->quarterly_price, 2) }}
                            </td>

                            <td>
                                {{ $plan->currency }}
                                {{ number_format((float)$plan->annual_price, 2) }}
                            </td>

                            <td>
                                <small>
                                    Users: {{ $plan->max_users }}<br>
                                    Branches: {{ $plan->max_branches }}<br>
                                    Products: {{ number_format($plan->max_products) }}<br>
                                    Storage: {{ $plan->storage_limit_mb }} MB
                                </small>
                            </td>

                            <td>{{ $plan->features_count }}</td>

                            <td>
                                @if($plan->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>

                            <td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">

                                    <a href="{{ route('super.plans.edit', $plan) }}"
                                       class="btn btn-sm btn-primary">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('super.plans.toggle', $plan) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="btn btn-sm btn-secondary">
                                            {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('super.plans.destroy', $plan) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this plan?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-danger">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:30px;">
                                No pricing plans have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $plans->links() }}
        </div>

    </div>
</div>

@endsection
