@extends('layouts.app')

@section('content')
@php
    $isProducts = $type === 'products';
    $isCategories = $type === 'categories';
    $isBrands = $type === 'brands';

    $singular = $isProducts ? 'product' : ($isCategories ? 'category' : 'brand');
    $baseRoute = 'catalogue.' . $type;
@endphp

<style>
    .catalogue-page {
        --cat-bg: #f5f8f6;
        --cat-card: #ffffff;
        --cat-text: #17251d;
        --cat-muted: #718078;
        --cat-border: #e4ebe6;
        --cat-soft: #eff7f1;
        --cat-green: #15803d;
        --cat-green-dark: #166534;
        color: var(--cat-text);
        width: 100%;
        min-width: 0;
    }

    html.dark .catalogue-page,
    body.dark .catalogue-page,
    [data-theme="dark"] .catalogue-page {
        --cat-bg: #101a14;
        --cat-card: #17231b;
        --cat-text: #e5eee8;
        --cat-muted: #9aaba0;
        --cat-border: #2a3a30;
        --cat-soft: #1d3325;
        --cat-green: #4ade80;
        --cat-green-dark: #22c55e;
    }

    .cat-heading {
        display:flex; align-items:flex-end; justify-content:space-between;
        gap:18px; flex-wrap:wrap; margin-bottom:24px;
    }
    .cat-eyebrow {
        color:var(--cat-green); font-size:11px; font-weight:800;
        letter-spacing:.13em; text-transform:uppercase; margin-bottom:7px;
    }
    .cat-title { font-size:clamp(24px,3vw,32px); font-weight:800; letter-spacing:-.04em; margin:0; color:var(--cat-text); }
    .cat-description { color:var(--cat-muted); margin:7px 0 0; font-size:14px; }
    .cat-btn {
        display:inline-flex; align-items:center; justify-content:center; gap:9px;
        min-height:43px; padding:0 16px; border-radius:11px; border:1px solid transparent;
        background:var(--cat-green); color:#fff; text-decoration:none; font-size:13px;
        font-weight:750; cursor:pointer; transition:.2s ease;
    }
    .cat-btn:hover { background:var(--cat-green-dark); color:#fff; transform:translateY(-1px); }
    .cat-btn-light { background:var(--cat-card); color:var(--cat-text); border-color:var(--cat-border); }
    .cat-btn-light:hover { background:var(--cat-soft); color:var(--cat-text); }
    .cat-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
    .cat-stat {
        background:var(--cat-card); border:1px solid var(--cat-border);
        border-radius:15px; padding:17px 18px; min-width:0;
    }
    .cat-stat-label { color:var(--cat-muted); font-size:12px; font-weight:650; }
    .cat-stat-value { color:var(--cat-text); font-size:25px; font-weight:800; margin-top:7px; letter-spacing:-.04em; }
    .cat-stat-note { color:var(--cat-muted); font-size:11px; margin-top:3px; }
    .cat-panel { background:var(--cat-card); border:1px solid var(--cat-border); border-radius:16px; overflow:hidden; }
    .cat-toolbar {
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        flex-wrap:wrap; padding:16px; border-bottom:1px solid var(--cat-border);
    }
    .cat-toolbar-title { font-size:14px; font-weight:800; color:var(--cat-text); }
    .cat-toolbar-sub { font-size:12px; color:var(--cat-muted); margin-top:3px; }
    .cat-filters { display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
    .cat-control {
        height:40px; min-width:0; border:1px solid var(--cat-border); border-radius:10px;
        background:var(--cat-card); color:var(--cat-text); padding:0 12px; outline:none;
        font-size:13px; width:auto;
    }
    .cat-control:focus { border-color:var(--cat-green); box-shadow:0 0 0 3px color-mix(in srgb,var(--cat-green) 13%,transparent); }
    .cat-search { width:min(250px,70vw); }
    .cat-table-wrap { width:100%; overflow-x:auto; }
    .cat-table { width:100%; border-collapse:collapse; min-width:760px; }
    .cat-table th {
        padding:12px 16px; text-align:left; white-space:nowrap; background:var(--cat-bg);
        color:var(--cat-muted); font-size:10px; text-transform:uppercase;
        letter-spacing:.09em; font-weight:800;
    }
    .cat-table td { padding:13px 16px; border-top:1px solid var(--cat-border); color:var(--cat-text); font-size:13px; vertical-align:middle; }
    .cat-table tbody tr:hover { background:var(--cat-bg); }
    .cat-item { display:flex; align-items:center; gap:11px; min-width:180px; }
    .cat-thumb {
        width:42px; height:42px; flex:0 0 42px; border-radius:11px;
        display:grid; place-items:center; overflow:hidden; background:var(--cat-soft);
        color:var(--cat-green); font-weight:800;
    }
    .cat-thumb img { width:100%; height:100%; object-fit:cover; }
    .cat-item-name { font-weight:750; color:var(--cat-text); }
    .cat-item-meta { color:var(--cat-muted); font-size:11px; margin-top:3px; }
    .cat-muted { color:var(--cat-muted); }
    .cat-badge {
        display:inline-flex; align-items:center; padding:5px 9px; border-radius:999px;
        font-size:10px; font-weight:800; white-space:nowrap;
        background:var(--cat-soft); color:var(--cat-green);
    }
    .cat-badge.off { background:rgba(148,163,184,.14); color:var(--cat-muted); }
    .cat-actions { display:flex; align-items:center; justify-content:flex-end; gap:7px; }
    .cat-icon-btn {
        width:34px; height:34px; display:inline-grid; place-items:center; border-radius:9px;
        border:1px solid var(--cat-border); color:var(--cat-muted); background:var(--cat-card);
        text-decoration:none; cursor:pointer;
    }
    .cat-icon-btn:hover { color:var(--cat-green); background:var(--cat-soft); }
    .cat-icon-btn.delete:hover { color:#dc2626; background:rgba(220,38,38,.08); }
    .cat-empty { text-align:center; padding:55px 20px; color:var(--cat-muted); }
    .cat-empty-icon { width:54px; height:54px; margin:0 auto 13px; border-radius:16px; background:var(--cat-soft); color:var(--cat-green); display:grid; place-items:center; font-size:22px; }
    .cat-pagination { padding:15px 17px; border-top:1px solid var(--cat-border); }
    .cat-pagination nav { display:flex; justify-content:flex-end; }
    .cat-pagination svg { width:16px; height:16px; }
    .cat-pagination p { color:var(--cat-muted); font-size:12px; }
    .cat-alert { padding:12px 15px; border-radius:11px; margin-bottom:15px; font-size:13px; }
    .cat-alert.success { background:#dcfce7; color:#166534; }
    .cat-alert.error { background:#fee2e2; color:#991b1b; }
    @media(max-width:700px) {
        .cat-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .cat-stat:last-child { grid-column:1/-1; }
        .cat-toolbar { align-items:stretch; }
        .cat-filters { width:100%; }
        .cat-search { width:100%; flex:1 1 100%; }
        .cat-filters select { flex:1; }
        .cat-heading .cat-btn { width:100%; }
    }
    @media(max-width:420px) {
        .cat-stats { gap:9px; }
        .cat-stat { padding:13px; }
        .cat-stat-value { font-size:21px; }
    }
</style>

<div class="catalogue-page">
    <div class="cat-heading">
        <div>
            <div class="cat-eyebrow">Inventory management / Catalogue</div>
            <h1 class="cat-title">{{ $title }}</h1>
            <p class="cat-description">{{ $description }}</p>
        </div>

        <a href="{{ route($createRoute) }}" class="cat-btn">
            <span>＋</span> Add {{ $singular }}
        </a>
    </div>

    @if(session('success'))
        <div class="cat-alert success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="cat-alert error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="cat-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="cat-stats">
        <div class="cat-stat">
            <div class="cat-stat-label">Total {{ $title }}</div>
            <div class="cat-stat-value">{{ number_format($total) }}</div>
            <div class="cat-stat-note">All registered {{ $type }}</div>
        </div>
        <div class="cat-stat">
            <div class="cat-stat-label">Active</div>
            <div class="cat-stat-value">{{ number_format($activeCount) }}</div>
            <div class="cat-stat-note">Currently enabled</div>
        </div>
        <div class="cat-stat">
            <div class="cat-stat-label">On this page</div>
            <div class="cat-stat-value">{{ number_format($items->count()) }}</div>
            <div class="cat-stat-note">Displayed records</div>
        </div>
    </div>

    <div class="cat-panel">
        <div class="cat-toolbar">
            <div>
                <div class="cat-toolbar-title">All {{ $title }}</div>
                <div class="cat-toolbar-sub">Search and manage your catalogue records</div>
            </div>

            <form method="GET" action="{{ route($baseRoute . '.index') }}" class="cat-filters">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search {{ $type }}..."
                    class="cat-control cat-search"
                >

                @if($isProducts)
                    <select name="category" class="cat-control">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="brand" class="cat-control">
                        <option value="">All brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(request('brand') == $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="status" class="cat-control">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>

                <button type="submit" class="cat-btn">Filter</button>
                @if(request()->hasAny(['search', 'category', 'brand', 'status']))
                    <a href="{{ route($baseRoute . '.index') }}" class="cat-btn cat-btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="cat-table-wrap">
            <table class="cat-table">
                <thead>
                    <tr>
                        @if($isProducts)
                            <th>Product</th>
                            <th>SKU / Barcode</th>
                            <th>Category / Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        @elseif($isCategories)
                            <th>Category</th>
                            <th>Parent category</th>
                            <th>Products</th>
                            <th>Status</th>
                        @else
                            <th>Brand</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Status</th>
                        @endif
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($items as $item)
                    <tr>
                        @if($isProducts)
                            <td>
                                <div class="cat-item">
                                    <div class="cat-thumb">
                                        @if($item->image_path)
                                            <img src="{{ asset('storage/' . $item->image_path) }}" alt="">
                                        @else
                                            {{ strtoupper(substr($item->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="cat-item-name">{{ $item->name }}</div>
                                        <div class="cat-item-meta">{{ ucfirst($item->unit ?? 'piece') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $item->sku ?: '—' }}</div>
                                <div class="cat-item-meta">{{ $item->barcode ?: 'No barcode' }}</div>
                            </td>
                            <td>
                                <div>{{ $item->category?->name ?? 'Uncategorised' }}</div>
                                <div class="cat-item-meta">{{ $item->brand?->name ?? 'No brand' }}</div>
                            </td>
                            <td>
                                <strong>{{ number_format((float)$item->price, 2) }}</strong>
                                <div class="cat-item-meta">Cost: {{ number_format((float)$item->cost, 2) }}</div>
                            </td>
                            <td>
                                @if($item->track_stock)
                                    <strong>{{ number_format((int)$item->stock) }}</strong>
                                    <div class="cat-item-meta">Reorder at {{ $item->reorder_level }}</div>
                                @else
                                    <span class="cat-muted">Not tracked</span>
                                @endif
                            </td>
                            <td>
                                <span class="cat-badge {{ $item->active ? '' : 'off' }}">
                                    {{ $item->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        @elseif($isCategories)
                            <td>
                                <div class="cat-item">
                                    <div class="cat-thumb">
                                        @if($item->image_path)
                                            <img src="{{ asset('storage/' . $item->image_path) }}" alt="">
                                        @else
                                            {{ strtoupper(substr($item->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="cat-item-name">{{ $item->name }}</div>
                                        <div class="cat-item-meta">{{ $item->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $item->parent?->name ?? '—' }}</td>
                            <td>{{ number_format($item->products_count) }}</td>
                            <td>
                                <span class="cat-badge {{ $item->is_active ? '' : 'off' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        @else
                            <td>
                                <div class="cat-item">
                                    <div class="cat-thumb">
                                        @if($item->logo_path)
                                            <img src="{{ asset('storage/' . $item->logo_path) }}" alt="">
                                        @else
                                            {{ strtoupper(substr($item->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="cat-item-name">{{ $item->name }}</div>
                                        <div class="cat-item-meta">{{ $item->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="cat-muted">{{ \Illuminate\Support\Str::limit($item->description ?: 'No description', 55) }}</td>
                            <td>{{ number_format($item->products_count) }}</td>
                            <td>
                                <span class="cat-badge {{ $item->is_active ? '' : 'off' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        @endif

                        <td>
                            <div class="cat-actions">
                                <a
                                    href="{{ route($baseRoute . '.edit', $item) }}"
                                    class="cat-icon-btn"
                                    title="Edit"
                                    aria-label="Edit"
                                >✎</a>

                                <form
                                    method="POST"
                                    action="{{ route($baseRoute . '.destroy', $item) }}"
                                    onsubmit="return confirm('Are you sure you want to delete this {{ $singular }}?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cat-icon-btn delete" title="Delete" aria-label="Delete">⌫</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="cat-empty">
                                <div class="cat-empty-icon">⌕</div>
                                <strong>No {{ $type }} found</strong>
                                <p>Try changing your search or add your first {{ $singular }}.</p>
                                <a href="{{ route($createRoute) }}" class="cat-btn">Add {{ $singular }}</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="cat-pagination">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection