<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('features')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('super.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('super.plans.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePlan($request);

        DB::transaction(function () use ($data) {
            $features = $data['features'] ?? [];
            unset($data['features']);

            $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

            $plan = Plan::create($data);

            $this->saveFeatures($plan, $features);
        });

        return redirect()
            ->route('super.plans.index')
            ->with('success', 'Pricing plan created successfully.');
    }

    public function edit(Plan $plan)
    {
        $plan->load('features');

        return view('super.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request, $plan);

        DB::transaction(function () use ($data, $plan) {
            $features = $data['features'] ?? [];
            unset($data['features']);

            $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

            $plan->update($data);

            $this->saveFeatures($plan, $features);
        });

        return redirect()
            ->route('super.plans.index')
            ->with('success', 'Pricing plan updated successfully.');
    }

    public function toggle(Plan $plan)
    {
        $plan->update([
            'is_active' => !$plan->is_active,
        ]);

        return back()->with(
            'success',
            'Plan status updated successfully.'
        );
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()
            ->route('super.plans.index')
            ->with('success', 'Pricing plan deleted successfully.');
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('plans', 'slug')->ignore($plan?->id),
            ],

            'description' => ['nullable', 'string'],

            'monthly_price' => ['required', 'numeric', 'min:0'],
            'quarterly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],

            'currency' => ['required', 'string', 'size:3'],

            'max_users' => ['required', 'integer', 'min:1'],
            'max_branches' => ['required', 'integer', 'min:1'],
            'max_products' => ['required', 'integer', 'min:1'],
            'storage_limit_mb' => ['required', 'integer', 'min:1'],

            'trial_days' => ['required', 'integer', 'min:0'],

            'is_active' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],

            'sort_order' => ['nullable', 'integer', 'min:0'],

            'features' => ['nullable', 'array'],
            'features.*.name' => ['nullable', 'string', 'max:255'],
            'features.*.slug' => ['nullable', 'string', 'max:255'],
            'features.*.description' => ['nullable', 'string'],
            'features.*.value' => ['nullable', 'string', 'max:255'],
            'features.*.is_enabled' => ['nullable', 'boolean'],
        ]);
    }

    private function saveFeatures(Plan $plan, array $features): void
    {
        $submittedSlugs = [];

        foreach ($features as $feature) {
            $name = trim($feature['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $slug = Str::slug($feature['slug'] ?? '') ?: Str::slug($name);

            $submittedSlugs[] = $slug;

            $plan->features()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $feature['description'] ?? null,
                    'value' => $feature['value'] ?? null,
                    'is_enabled' => (bool) ($feature['is_enabled'] ?? false),
                ]
            );
        }

        if (empty($submittedSlugs)) {
            $plan->features()->delete();
        } else {
            $plan->features()
                ->whereNotIn('slug', $submittedSlugs)
                ->delete();
        }
    }
}
