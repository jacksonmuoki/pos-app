@extends('layouts.app')

@section('content')
@php
    $editing = $item->exists;
    $baseRoute = 'catalogue.' . $type;
    $isProducts = $type === 'products';
    $isCategories = $type === 'categories';
    $isBrands = $type === 'brands';

    $imagePath = $isProducts || $isCategories
        ? $item->image_path
        : $item->logo_path;

    $uploadField = $isBrands ? 'logo' : 'image';
    $statusField = $isProducts ? 'active' : 'is_active';
    $statusValue = old($statusField, $item->{$statusField} ?? true);
@endphp

<style>
    .catalogue-form-page {
        --f-card:#fff; --f-text:#17251d; --f-muted:#718078;
        --f-border:#e4ebe6; --f-bg:#f5f8f6; --f-green:#15803d;
        color:var(--f-text); width:100%; min-width:0;
    }
    html.dark .catalogue-form-page,
    body.dark .catalogue-form-page,
    [data-theme="dark"] .catalogue-form-page {
        --f-card:#17231b; --f-text:#e5eee8; --f-muted:#9aaba0;
        --f-border:#2a3a30; --f-bg:#101a14; --f-green:#4ade80;
    }
    .form-heading { display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap; margin-bottom:22px; }
    .form-eyebrow { color:var(--f-green); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.12em; margin-bottom:7px; }
    .form-title { font-size:clamp(24px,3vw,31px); font-weight:800; letter-spacing:-.04em; margin:0; color:var(--f-text); }
    .form-subtitle { color:var(--f-muted); font-size:13px; margin:7px 0 0; }
    .form-back { display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:0 14px; border:1px solid var(--f-border); border-radius:10px; background:var(--f-card); color:var(--f-text); text-decoration:none; font-size:13px; font-weight:700; }
    .form-card { background:var(--f-card); border:1px solid var(--f-border); border-radius:16px; overflow:hidden; }
    .form-card-head { padding:17px 20px; border-bottom:1px solid var(--f-border); }
    .form-card-head strong { display:block; font-size:14px; }
    .form-card-head span { color:var(--f-muted); font-size:12px; display:block; margin-top:4px; }
    .form-body { padding:21px; }
    .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
    .form-field { min-width:0; }
    .form-field.full { grid-column:1/-1; }
    .form-label { display:block; font-size:12px; font-weight:750; color:var(--f-text); margin-bottom:7px; }
    .form-label .optional { color:var(--f-muted); font-weight:500; }
    .form-input, .form-select, .form-textarea {
        display:block; width:100%; min-width:0; min-height:43px; border:1px solid var(--f-border);
        border-radius:10px; padding:10px 12px; background:var(--f-card); color:var(--f-text);
        font:inherit; font-size:13px; outline:none;
    }
    .form-textarea { min-height:105px; resize:vertical; }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color:var(--f-green); box-shadow:0 0 0 3px color-mix(in srgb,var(--f-green) 13%,transparent); }
    .form-hint { color:var(--f-muted); font-size:11px; margin-top:6px; }
    .form-error { color:#dc2626; font-size:11px; margin-top:5px; }
    .form-checks { display:flex; flex-wrap:wrap; gap:12px; }
    .form-check {
        display:flex; align-items:center; gap:9px; padding:12px 13px;
        border:1px solid var(--f-border); border-radius:11px; background:var(--f-bg);
        color:var(--f-text); font-size:12px; font-weight:650; flex:1 1 180px;
    }
    .form-check input { accent-color:var(--f-green); width:16px; height:16px; }
    .form-upload {
        border:1px dashed var(--f-border); border-radius:12px; padding:16px;
        background:var(--f-bg);
    }
    .form-preview { width:76px; height:76px; border-radius:12px; object-fit:cover; margin-bottom:10px; border:1px solid var(--f-border); }
    .form-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:16px 21px; border-top:1px solid var(--f-border); flex-wrap:wrap; }
    .form-btn { min-height:43px; padding:0 17px; border-radius:10px; border:1px solid transparent; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:750; cursor:pointer; text-decoration:none; }
    .form-btn-primary { background:var(--f-green); color:#fff; }
    .form-btn-primary:hover { filter:brightness(.94); color:#fff; }
    .form-btn-secondary { background:var(--f-card); color:var(--f-text); border-color:var(--f-border); }
    .form-section-title { grid-column:1/-1; font-size:12px; font-weight:850; color:var(--f-green); text-transform:uppercase; letter-spacing:.08em; padding-bottom:9px; border-bottom:1px solid var(--f-border); margin-top:5px; }
    @media(max-width:650px) {
        .form-grid { grid-template-columns:1fr; gap:14px; }
        .form-field.full, .form-section-title { grid-column:1; }
        .form-body { padding:16px; }
        .form-actions { padding:14px 16px; }
        .form-actions .form-btn { flex:1; }
    }
</style>

<div class="catalogue-form-page">
    <div class="form-heading">
        <div>
            <div class="form-eyebrow">Catalogue management / {{ ucfirst($type) }}</div>
            <h1 class="form-title">{{ $title }}</h1>
            <p class="form-subtitle">
                {{ $editing ? 'Update the information below and save your changes.' : 'Enter the details below to add a new record to your catalogue.' }}
            </p>
        </div>

        <a href="{{ route($baseRoute . '.index') }}" class="form-back">← Back to {{ ucfirst($type) }}</a>
    </div>

    @if($errors->any())
        <div style="padding:13px 15px;border-radius:10px;background:#fee2e2;color:#991b1b;margin-bottom:15px;font-size:13px;">
            <strong>Please correct the following:</strong>
            <ul style="margin:7px 0 0 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $editing ? route($baseRoute . '.update', $item) : route($baseRoute . '.store') }}"
        enctype="multipart/form-data"
        class="form-card"
    >
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="form-card-head">
            <strong>{{ $editing ? 'Record information' : 'New record information' }}</strong>
            <span>Fields marked as required must be completed.</span>
        </div>

        <div class="form-body">
            <div class="form-grid">

                @if($isProducts)
                    <div class="form-section-title">Basic information</div>

                    <div class="form-field full">
                        <label class="form-label">Product name *</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name', $item->name) }}" required maxlength="255" placeholder="e.g. Premium cooking oil">
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label">SKU <span class="optional">(optional)</span></label>
                        <input class="form-input" name="sku" value="{{ old('sku', $item->sku) }}" maxlength="100" placeholder="e.g. PROD-001">
                        @error('sku')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label">Barcode <span class="optional">(optional)</span></label>
                        <input class="form-input" name="barcode" value="{{ old('barcode', $item->barcode) }}" maxlength="100" placeholder="Scan or enter barcode">
                        @error('barcode')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Brand</label>
                        <select class="form-select" name="brand_id">
                            <option value="">Select brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id) == $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field full">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" name="description" placeholder="Describe this product...">{{ old('description', $item->description) }}</textarea>
                    </div>

                    <div class="form-section-title">Pricing and stock</div>

                    <div class="form-field">
                        <label class="form-label">Selling price (KES) *</label>
                        <input class="form-input" type="number" name="price" min="0" step="0.01" required value="{{ old('price', $item->price) }}" placeholder="0.00">
                        @error('price')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label">Cost price (KES)</label>
                        <input class="form-input" type="number" name="cost" min="0" step="0.01" value="{{ old('cost', $item->cost ?? 0) }}" placeholder="0.00">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Wholesale price (KES)</label>
                        <input class="form-input" type="number" name="wholesale_price" min="0" step="0.01" value="{{ old('wholesale_price', $item->wholesale_price) }}" placeholder="Optional">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Unit *</label>
                        <select class="form-select" name="unit" required>
                            @foreach(['piece', 'packet', 'box', 'carton', 'kg', 'gram', 'litre', 'ml', 'metre', 'dozen', 'pair', 'service'] as $unit)
                                <option value="{{ $unit }}" @selected(old('unit', $item->unit ?? 'piece') === $unit)>{{ ucfirst($unit) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Product type *</label>
                        <select class="form-select" name="product_type" required>
                            <option value="goods" @selected(old('product_type', $item->product_type ?? 'goods') === 'goods')>Physical goods</option>
                            <option value="service" @selected(old('product_type', $item->product_type ?? 'goods') === 'service')>Service</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Opening stock</label>
                        <input class="form-input" type="number" name="stock" min="0" step="1" value="{{ old('stock', $item->stock ?? 0) }}">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Low-stock alert level</label>
                        <input class="form-input" type="number" name="reorder_level" min="0" step="1" value="{{ old('reorder_level', $item->reorder_level ?? 0) }}">
                    </div>

                    <div class="form-section-title">Product image and settings</div>

                    <div class="form-field full">
                        <label class="form-label">Product image <span class="optional">(maximum 2 MB)</span></label>
                        <div class="form-upload">
                            @if($imagePath)
                                <img class="form-preview" src="{{ asset('storage/' . $imagePath) }}" alt="Current product image">
                            @endif
                            <input class="form-input" type="file" name="image" accept="image/*">
                            <div class="form-hint">Use a clear JPG, PNG or WebP product image.</div>
                        </div>
                    </div>

                    <div class="form-field full">
                        <div class="form-checks">
                            <label class="form-check">
                                <input type="hidden" name="track_stock" value="0">
                                <input type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $item->track_stock ?? true))>
                                Track stock levels
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="is_taxable" value="0">
                                <input type="checkbox" name="is_taxable" value="1" @checked(old('is_taxable', $item->is_taxable ?? false))>
                                Apply tax to this product
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" @checked($statusValue)>
                                Product is active
                            </label>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Tax rate (%)</label>
                        <input class="form-input" type="number" name="tax_rate" min="0" max="100" step="0.01" value="{{ old('tax_rate', $item->tax_rate) }}" placeholder="Optional">
                    </div>

                @elseif($isCategories)
                    <div class="form-field full">
                        <label class="form-label">Category name *</label>
                        <input class="form-input" type="text" name="name" required maxlength="150" value="{{ old('name', $item->name) }}" placeholder="e.g. Beverages">
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-field full">
                        <label class="form-label">Parent category <span class="optional">(optional)</span></label>
                        <select class="form-select" name="parent_id">
                            <option value="">No parent category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('parent_id', $item->parent_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field full">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" name="description" placeholder="What products belong in this category?">{{ old('description', $item->description) }}</textarea>
                    </div>

                    <div class="form-field full">
                        <label class="form-label">Category image <span class="optional">(maximum 2 MB)</span></label>
                        <div class="form-upload">
                            @if($imagePath)
                                <img class="form-preview" src="{{ asset('storage/' . $imagePath) }}" alt="Current category image">
                            @endif
                            <input class="form-input" type="file" name="image" accept="image/*">
                        </div>
                    </div>

                    <div class="form-field full">
                        <div class="form-checks">
                            <label class="form-check">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($statusValue)>
                                Category is active
                            </label>
                        </div>
                    </div>

                @else
                    <div class="form-field full">
                        <label class="form-label">Brand name *</label>
                        <input class="form-input" type="text" name="name" required maxlength="150" value="{{ old('name', $item->name) }}" placeholder="e.g. Coca-Cola">
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-field full">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" name="description" placeholder="Tell customers about this brand...">{{ old('description', $item->description) }}</textarea>
                    </div>

                    <div class="form-field full">
                        <label class="form-label">Brand logo <span class="optional">(maximum 2 MB)</span></label>
                        <div class="form-upload">
                            @if($imagePath)
                                <img class="form-preview" src="{{ asset('storage/' . $imagePath) }}" alt="Current brand logo">
                            @endif
                            <input class="form-input" type="file" name="logo" accept="image/*">
                        </div>
                    </div>

                    <div class="form-field full">
                        <div class="form-checks">
                            <label class="form-check">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($statusValue)>
                                Brand is active
                            </label>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route($baseRoute . '.index') }}" class="form-btn form-btn-secondary">Cancel</a>
            <button type="submit" class="form-btn form-btn-primary">
                {{ $editing ? 'Save changes' : 'Create ' . rtrim($type, 's') }}
            </button>
        </div>
    </form>
</div>
@endsection