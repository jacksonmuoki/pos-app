<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    /**
     * Display all subscriptions.
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['plan'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        $total = Subscription::count();
        $active = Subscription::where('status', 'active')->count();
        $pending = Subscription::where('status', 'pending')->count();
        $cancelled = Subscription::where('status', 'cancelled')->count();

        return view('super.subscriptions.index', compact(
            'subscriptions',
            'total',
            'active',
            'pending',
            'cancelled'
        ));
    }

    /**
     * Show the subscription assignment form.
     */
    public function create()
    {
        $tenants = Tenant::all()->sortBy(
            fn ($tenant) => strtolower($tenant->name ?? $tenant->id)
        )->values();

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('super.subscriptions.create', compact(
            'tenants',
            'plans'
        ));
    }

    /**
     * Assign a plan to a company.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => [
                'required',
                'string',
                'exists:tenants,id',
            ],

            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id',
            ],

            'billing_cycle' => [
                'required',
                Rule::in(['monthly', 'quarterly', 'annual']),
            ],

            'start_trial' => [
                'nullable',
                'boolean',
            ],
        ]);

        $tenant = Tenant::findOrFail($data['tenant_id']);

        $plan = Plan::where('is_active', true)
            ->findOrFail($data['plan_id']);

        $amount = $plan->getPriceForCycle($data['billing_cycle']);

        $startTrial = (bool) ($data['start_trial'] ?? false);

        if ($startTrial && $plan->trial_days < 1) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_trial' => 'This plan does not offer a trial.',
                ]);
        }

        $subscription = DB::transaction(function () use (
            $data,
            $tenant,
            $plan,
            $amount,
            $startTrial
        ) {
            $now = now();

            return Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $data['billing_cycle'],
                'amount' => $amount,
                'currency' => $plan->currency,
                'status' => $startTrial ? 'trial' : 'pending',
                'starts_at' => $startTrial ? $now : null,
                'trial_ends_at' => $startTrial
                    ? $now->copy()->addDays($plan->trial_days)
                    : null,
                'auto_renew' => false,
            ]);
        });

        ActivityLog::record('subscription.created', $tenant->id, [
            'subscription_id' => $subscription->id,
            'plan' => $plan->name,
            'billing_cycle' => $data['billing_cycle'],
            'status' => $subscription->status,
        ]);

        return redirect()
            ->route('super.subscriptions.show', $subscription->id)
            ->with('status', 'Subscription successfully created.');
    }

    /**
     * Display a subscription.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load('plan');

        $tenant = Tenant::find($subscription->tenant_id);

        return view('super.subscriptions.show', compact(
            'subscription',
            'tenant'
        ));
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        if (in_array($subscription->status, ['cancelled', 'expired'], true)) {
            return back()->withErrors([
                'subscription' => 'This subscription is already closed.',
            ]);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
            'metadata' => array_merge(
                $subscription->metadata ?? [],
                ['cancellation_reason' => $data['reason'] ?? null]
            ),
        ]);

        ActivityLog::record(
            'subscription.cancelled',
            $subscription->tenant_id,
            [
                'subscription_id' => $subscription->id,
                'reason' => $data['reason'] ?? null,
            ]
        );

        return back()->with('status', 'Subscription cancelled.');
    }
}
