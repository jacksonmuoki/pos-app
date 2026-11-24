
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

    .form-heading {
        display:flex; justify-content:space-between; align-items:center;
        gap:15px; flex-wrap:wrap; margin-bottom:22px;
    }

    .form-eyebrow {
        color:var(--f-green); font-size:11px; font-weight:800;
        text-transform:uppercase; letter-spacing:.12em; margin-bottom:7px;
    }

    .form-title {
        font-size:clamp(24px,3vw,31px); font-weight:800;
        letter-spacing:-.04em; margin:0; color:var(--f-text);
    }

    .form-subtitle {
        color:var(--f-muted); font-size:13px; margin:7px 0 0;
    }

    .form-back {
        display:inline-flex; align-items:center; justify-content:center;
        min-height:40px; padding:0 14px; border:1px solid var(--f-border);
        border-radius:10px; background:var(--f-card); color:var(--f-text);
        text-decoration:none; font-size:13px; font-weight:700;
    }

    .form-card {
        background:var(--f-card); border:1px solid var(--f-border);
        border-radius:16px; overflow:hidden;
    }

    .form-card-head { padding:17px 20px; border-bottom:1px solid var(--f-border); }
    .form-card-head strong { display:block; font-size:14px; }

    .form-card-head span {
        color:var(--f-muted); font-size:12px;
        display:block; margin-top:4px;
    }

    .form-body { padding:21px; }

    .form-grid {
        display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
        gap:18px;
    }

    .form-field { min-width:0; }
    .form-field.full { grid-column:1/-1; }

    .form-label {
        display:block; font-size:12px; font-weight:750;
        color:var(--f-text); margin-bottom:7px;
    }

    .form-label .optional { color:var(--f-muted); font-weight:500; }

    .form-input, .form-select, .form-textarea {
        display:block; width:100%; min-width:0; min-height:43px;
        border:1px solid var(--f-border); border-radius:10px;
        padding:10px 12px; background:var(--f-card);
        color:var(--f-text); font:inherit; font-size:13px; outline:none;
    }

    .form-textarea { min-height:105px; resize:vertical; }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color:var(--f-green);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--f-green) 13%,transparent);
    }

    .form-hint { color:var(--f-muted); font-size:11px; margin-top:6px; }
    .form-error { color:#dc2626; font-size:11px; margin-top:5px; }

    .form-checks { display:flex; flex-wrap:wrap; gap:12px; }

    .form-check {
        display:flex; align-items:center; gap:9px; padding:12px 13px;
        border:1px solid var(--f-border); border-radius:11px;
        background:var(--f-bg); color:var(--f-text);
        font-size:12px; font-weight:650; flex:1 1 180px;
    }

    .form-check input { accent-color:var(--f-green); width:16px; height:16px; }

    .form-upload {
        border:1px dashed var(--f-border); border-radius:12px;
        padding:16px; background:var(--f-bg);
    }

    .form-preview {
        width:76px; height:76px; border-radius:12px;
        object-fit:cover; margin-bottom:10px;
        border:1px solid var(--f-border);
    }

    .form-actions {
        display:flex; align-items:center; justify-content:flex-end;
        gap:10px; padding:16px 21px;
        border-top:1px solid var(--f-border); flex-wrap:wrap;
    }

    .form-btn {
        min-height:43px; padding:0 17px; border-radius:10px;
        border:1px solid transparent; display:inline-flex;
        align-items:center; justify-content:center;
        font-size:13px; font-weight:750; cursor:pointer; text-decoration:none;
    }

    .form-btn-primary { background:var(--f-green); color:#fff; }
    .form-btn-primary:hover { filter:brightness(.94); color:#fff; }

    .form-btn-secondary {
        background:var(--f-card); color:var(--f-text);
        border-color:var(--f-border);
    }

    .form-section-title {
        grid-column:1/-1; font-size:12px; font-weight:850;
        color:var(--f-green); text-transform:uppercase;
        letter-spacing:.08em; padding-bottom:9px;
        border-bottom:1px solid var(--f-border); margin-top:5px;
    }

    /* Drag and drop image uploader */

    .product-dropzone {
        position:relative;
        border:2px dashed var(--f-border);
        border-radius:14px;
        padding:28px 16px;
        background:var(--f-bg);
        text-align:center;
        cursor:pointer;
        transition:all .2s ease;
        outline:none;
    }

    .product-dropzone:hover,
    .product-dropzone.drag-over,
    .product-dropzone:focus-visible {
        border-color:var(--f-green);
        background:color-mix(in srgb,var(--f-green) 7%,var(--f-bg));
    }

    .product-drop-icon {
        width:48px;
        height:48px;
        margin:0 auto 12px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:13px;
        background:color-mix(in srgb,var(--f-green) 12%,transparent);
        color:var(--f-green);
        font-size:25px;
        font-weight:800;
    }

    .product-drop-title {
        font-size:14px;
        font-weight:800;
        color:var(--f-text);
    }

    .product-drop-hint {
        font-size:12px;
        color:var(--f-muted);
        margin-top:7px;
    }

    .product-browse {
        display:inline-flex;
        margin-top:14px;
        padding:10px 17px;
        border-radius:9px;
        background:var(--f-green);
        color:white;
        font-size:12px;
        font-weight:750;
    }

    .product-file-input { display:none !important; }

    .product-image-preview {
        display:none;
        margin-top:16px;
        padding:15px;
        border:1px solid var(--f-border);
        border-radius:12px;
        background:var(--f-card);
        text-align:center;
    }

    .product-image-preview img {
        width:150px;
        height:150px;
        object-fit:contain;
        border-radius:12px;
        border:1px solid var(--f-border);
        background:#fff;
        padding:5px;
    }

    .product-image-name {
        font-size:12px;
        color:var(--f-muted);
        margin-top:9px;
        overflow-wrap:anywhere;
    }

    .product-image-remove {
        display:inline-flex;
        margin-top:10px;
        padding:8px 13px;
        border:1px solid var(--f-border);
        border-radius:8px;
        background:var(--f-card);
        color:#dc2626;
        font-size:11px;
        font-weight:700;
        cursor:pointer;
    }

    .product-image-remove:hover {
        background:#fef2f2;
        border-color:#fecaca;
    }

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
    </div>

    @if($errors->any())
        <div class="form-error" style="margin-bottom:15px;">
            <ul style="padding-left:20px;">
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
                        <label class="form-label">
                            Product image <span class="optional">(maximum 2 MB)</span>
                        </label>

                        <div
                            class="product-dropzone"
                            id="productDropzone"
                            tabindex="0"
                            role="button"
                            aria-label="Choose or drop a product image"
                        >
                            <div class="product-drop-icon">↑</div>

                            <div class="product-drop-title">
                                Drag and drop your product image here
                            </div>

                            <div class="product-drop-hint">
                                Or browse your computer, or paste an image
                            </div>

                            <span class="product-browse">Browse images</span>

                            <input
                                type="file"
                                name="image"
                                id="productImageInput"
                                class="product-file-input"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >
                        </div>

                        <div
                            class="product-image-preview"
                            id="productImagePreview"
                            style="{{ $imagePath ? 'display:block' : '' }}"
                        >
                            <img
                                id="productPreviewImage"
                                src="{{ $imagePath ? asset('storage/' . $imagePath) : '' }}"
                                alt="Product image preview"
                                style="{{ $imagePath ? '' : 'display:none' }}"
                            >

                            <div class="product-image-name" id="productImageName">
                                {{ $imagePath ? 'Current product image' : '' }}
                            </div>

                            <button
                                type="button"
                                class="product-image-remove"
                                id="removeProductImage"
                            >
                                Remove / Clear image
                            </button>
                        </div>

                        <div class="form-error" id="productImageError"></div>

                        <div class="form-hint">
                            Supported formats: JPG, JPEG, PNG and WEBP. Maximum size: 2 MB.
                        </div>

                        @error('image')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
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

@if($isProducts)
<script>
document.addEventListener('DOMContentLoaded', function () {

    const dropzone = document.getElementById('productDropzone');
    const input = document.getElementById('productImageInput');
    const preview = document.getElementById('productImagePreview');
    const previewImage = document.getElementById('productPreviewImage');
    const imageName = document.getElementById('productImageName');
    const removeButton = document.getElementById('removeProductImage');
    const error = document.getElementById('productImageError');

    if (!dropzone || !input) return;

    const currentImage = @json($imagePath ? asset('storage/' . $imagePath) : null);

    let previewUrl = null;

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    function showError(message = '') {
        error.textContent = message;
    }

    function selectImage(file) {

        showError();

        if (!file) return;

        if (!allowedTypes.includes(file.type)) {
            showError('Please select a JPG, PNG or WEBP image.');
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showError('Image must not exceed 2 MB.');
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);

        previewImage.src = previewUrl;
        previewImage.style.display = 'inline-block';

        imageName.textContent = file.name;
        preview.style.display = 'block';
    }

    // Open file browser
    dropzone.addEventListener('click', function () {
        input.click();
    });

    // Keyboard accessibility
    dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            input.click();
        }
    });

    // Manual image selection
    input.addEventListener('change', function () {
        if (this.files.length) {
            selectImage(this.files[0]);
        }
    });

    // Drag over
    ['dragenter', 'dragover'].forEach(event => {
        dropzone.addEventListener(event, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('drag-over');
        });
    });

    // Drag leave
    ['dragleave', 'dragend'].forEach(event => {
        dropzone.addEventListener(event, function (e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
        });
    });

    // Drop image
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();

        dropzone.classList.remove('drag-over');

        const files = e.dataTransfer.files;

        if (files.length) {
            selectImage(files[0]);
        }
    });

    // Paste image from clipboard
    dropzone.addEventListener('paste', function (e) {

        const items = e.clipboardData.items;

        for (const item of items) {

            if (item.type.startsWith('image/')) {

                const file = item.getAsFile();

                if (file) {
                    selectImage(file);
                    e.preventDefault();
                    return;
                }
            }
        }
    });

    // Remove selected image or restore existing image
    removeButton.addEventListener('click', function () {

        input.value = '';

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        showError();

        if (currentImage) {
            previewImage.src = currentImage;
            previewImage.style.display = 'inline-block';
            imageName.textContent = 'Current product image';
            preview.style.display = 'block';
        } else {
            previewImage.src = '';
            previewImage.style.display = 'none';
            imageName.textContent = '';
            preview.style.display = 'none';
        }
    });

});
</script>
@endif

@endsection
