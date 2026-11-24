
@php
    $featureRows = old('features', isset($plan)
        ? $plan->features->toArray()
        : []
    );

    while (count($featureRows) < 5) {
        $featureRows[] = [];
    }
@endphp

<div class="form-section">
    <h3>Basic Information</h3>

    <div class="form-group">
        <label>Plan Name</label>
        <input type="text"
               name="name"
               class="form-control"
               value="{{ old('name', $plan->name ?? '') }}"
               placeholder="e.g. Professional"
               required>
        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group">
        <label>Plan Slug</label>
        <input type="text"
               name="slug"
               class="form-control"
               value="{{ old('slug', $plan->slug ?? '') }}"
               placeholder="e.g. professional">
        @error('slug') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description"
                  class="form-control"
                  rows="3"
                  placeholder="Describe this subscription plan">{{ old('description', $plan->description ?? '') }}</textarea>
    </div>
</div>

<hr>

<div class="form-section">
    <h3>Pricing</h3>

    <div class="form-grid">
        <div class="form-group">
            <label>Monthly Price</label>
            <input type="number"
                   name="monthly_price"
                   class="form-control"
                   min="0"
                   step="0.01"
                   value="{{ old('monthly_price', $plan->monthly_price ?? '0.00') }}"
                   required>
            @error('monthly_price') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label>Quarterly Price</label>
            <input type="number"
                   name="quarterly_price"
                   class="form-control"
                   min="0"
                   step="0.01"
                   value="{{ old('quarterly_price', $plan->quarterly_price ?? '0.00') }}"
                   required>
            @error('quarterly_price') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label>Annual Price</label>
            <input type="number"
                   name="annual_price"
                   class="form-control"
                   min="0"
                   step="0.01"
                   value="{{ old('annual_price', $plan->annual_price ?? '0.00') }}"
                   required>
            @error('annual_price') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label>Currency</label>
            <select name="currency" class="form-control" required>
                <option value="KES" @selected(old('currency', $plan->currency ?? 'KES') === 'KES')>KES - Kenyan Shilling</option>
                <option value="USD" @selected(old('currency', $plan->currency ?? 'KES') === 'USD')>USD - US Dollar</option>
                <option value="UGX" @selected(old('currency', $plan->currency ?? 'KES') === 'UGX')>UGX - Uganda Shilling</option>
                <option value="TZS" @selected(old('currency', $plan->currency ?? 'KES') === 'TZS')>TZS - Tanzania Shilling</option>
            </select>
        </div>
    </div>
</div>

<hr>

<div class="form-section">
    <h3>Plan Limits</h3>

    <div class="form-grid">
        <div class="form-group">
            <label>Maximum Users / Staff</label>
            <input type="number" name="max_users" class="form-control"
                   min="1" value="{{ old('max_users', $plan->max_users ?? 1) }}" required>
        </div>

        <div class="form-group">
            <label>Maximum Branches</label>
            <input type="number" name="max_branches" class="form-control"
                   min="1" value="{{ old('max_branches', $plan->max_branches ?? 1) }}" required>
        </div>

        <div class="form-group">
            <label>Maximum Products</label>
            <input type="number" name="max_products" class="form-control"
                   min="1" value="{{ old('max_products', $plan->max_products ?? 100) }}" required>
        </div>

        <div class="form-group">
            <label>Storage Limit (MB)</label>
            <input type="number" name="storage_limit_mb" class="form-control"
                   min="1" value="{{ old('storage_limit_mb', $plan->storage_limit_mb ?? 500) }}" required>
        </div>

        <div class="form-group">
            <label>Trial Period (Days)</label>
            <input type="number" name="trial_days" class="form-control"
                   min="0" value="{{ old('trial_days', $plan->trial_days ?? 0) }}" required>
        </div>

        <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="sort_order" class="form-control"
                   min="0" value="{{ old('sort_order', $plan->sort_order ?? 0) }}">
        </div>
    </div>
</div>

<hr>

<div class="form-section">
    <h3>Plan Features</h3>
    <p>Add the features available under this plan. Leave unused rows blank.</p>

    @foreach($featureRows as $index => $feature)
        <div class="feature-row"
             style="border:1px solid #e5e7eb;padding:15px;margin-bottom:12px;border-radius:8px;">

            <div class="form-grid">
                <div class="form-group">
                    <label>Feature Name</label>
                    <input type="text"
                           name="features[{{ $index }}][name]"
                           class="form-control"
                           value="{{ $feature['name'] ?? '' }}"
                           placeholder="Inventory Management">
                </div>

                <div class="form-group">
                    <label>Feature Slug</label>
                    <input type="text"
                           name="features[{{ $index }}][slug]"
                           class="form-control"
                           value="{{ $feature['slug'] ?? '' }}"
                           placeholder="inventory-management">
                </div>

                <div class="form-group">
                    <label>Feature Value / Limit</label>
                    <input type="text"
                           name="features[{{ $index }}][value]"
                           class="form-control"
                           value="{{ $feature['value'] ?? '' }}"
                           placeholder="Unlimited or 100">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text"
                       name="features[{{ $index }}][description]"
                       class="form-control"
                       value="{{ $feature['description'] ?? '' }}"
                       placeholder="Describe this feature">
            </div>

            <input type="hidden"
                   name="features[{{ $index }}][is_enabled]"
                   value="0">

            <label style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox"
                       name="features[{{ $index }}][is_enabled]"
                       value="1"
                       @checked(old("features.$index.is_enabled", $feature['is_enabled'] ?? true))>
                Enable this feature
            </label>

        </div>
    @endforeach
</div>

<hr>

<div class="form-section">
    <h3>Plan Visibility</h3>

    <input type="hidden" name="is_active" value="0">
    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
        <input type="checkbox"
               name="is_active"
               value="1"
               @checked(old('is_active', $plan->is_active ?? true))>
        Make this plan active
    </label>

    <input type="hidden" name="is_popular" value="0">
    <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox"
               name="is_popular"
               value="1"
               @checked(old('is_popular', $plan->is_popular ?? false))>
        Mark as popular plan
    </label>
</div>

<div style="display:flex;gap:10px;margin-top:25px;">
    <button type="submit" class="btn btn-primary">
        {{ $buttonText }}
    </button>

    <a href="{{ route('super.plans.index') }}" class="btn btn-secondary">
        Cancel
    </a>
</div>
