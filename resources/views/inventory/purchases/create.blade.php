
@extends('layouts.app')

@section('title', 'New Purchase')
@section('page-heading', 'New Purchase')

@section('content')

@php
    $suppliers = $suppliers ?? collect();
    $products = $products ?? collect();
@endphp

<style>
    .purchase-create {
        --pc-bg: #f5f8f6;
        --pc-card: #ffffff;
        --pc-text: #17251d;
        --pc-muted: #718078;
        --pc-border: #e4ebe6;
        --pc-soft: #eff7f1;
        --pc-green: #15803d;
        --pc-green-dark: #166534;
        color: var(--pc-text);
    }

    [data-theme="dark"] .purchase-create,
    .dark .purchase-create {
        --pc-bg: #101a14;
        --pc-card: #17231b;
        --pc-text: #e5eee8;
        --pc-muted: #9aaba0;
        --pc-border: #2a3a30;
        --pc-soft: #1d3325;
        --pc-green: #4ade80;
        --pc-green-dark: #22c55e;
    }

    .purchase-create .pc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .purchase-create .pc-title {
        margin: 0;
        font-size: 23px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .purchase-create .pc-subtitle {
        margin: 5px 0 0;
        color: var(--pc-muted);
        font-size: 13px;
    }

    .purchase-create .pc-card {
        background: var(--pc-card);
        border: 1px solid var(--pc-border);
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .purchase-create .pc-card-header {
        padding: 17px 20px;
        border-bottom: 1px solid var(--pc-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .purchase-create .pc-card-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: var(--pc-text);
    }

    .purchase-create .pc-card-body {
        padding: 20px;
    }

    .purchase-create .pc-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .purchase-create .pc-field {
        min-width: 0;
    }

    .purchase-create .pc-field.full {
        grid-column: 1 / -1;
    }

    .purchase-create label {
        display: block;
        margin-bottom: 7px;
        color: var(--pc-text);
        font-size: 13px;
        font-weight: 650;
    }

    .purchase-create .pc-required {
        color: #dc2626;
    }

    .purchase-create input,
    .purchase-create select,
    .purchase-create textarea {
        width: 100%;
        min-height: 43px;
        padding: 10px 12px;
        border: 1px solid var(--pc-border);
        border-radius: 9px;
        background: var(--pc-card);
        color: var(--pc-text);
        font: inherit;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
        transition: border-color .2s, box-shadow .2s;
    }

    .purchase-create textarea {
        min-height: 85px;
        resize: vertical;
    }

    .purchase-create input:focus,
    .purchase-create select:focus,
    .purchase-create textarea:focus {
        border-color: var(--pc-green);
        box-shadow: 0 0 0 3px rgba(21,128,61,.10);
    }

    .purchase-create .pc-hint {
        display: block;
        margin-top: 6px;
        color: var(--pc-muted);
        font-size: 11px;
    }

    .purchase-create .pc-table-wrap {
        overflow-x: auto;
    }

    .purchase-create table {
        width: 100%;
        border-collapse: collapse;
        min-width: 650px;
    }

    .purchase-create thead {
        background: var(--pc-bg);
    }

    .purchase-create th {
        padding: 13px 15px;
        text-align: left;
        color: var(--pc-muted);
        font-size: 11px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: .5px;
        border-bottom: 1px solid var(--pc-border);
    }

    .purchase-create td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--pc-border);
        vertical-align: middle;
    }

    .purchase-create tbody tr:last-child td {
        border-bottom: 0;
    }

    .purchase-create .pc-row-number {
        color: var(--pc-muted);
        font-size: 12px;
        font-weight: 700;
        width: 45px;
    }

    .purchase-create .pc-remove {
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--pc-border);
        border-radius: 8px;
        background: transparent;
        color: #dc2626;
        cursor: pointer;
        font-size: 18px;
    }

    .purchase-create .pc-remove:hover {
        background: #fef2f2;
        border-color: #fecaca;
    }

    .purchase-create .pc-add {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 13px;
        border: 1px solid var(--pc-border);
        border-radius: 9px;
        background: var(--pc-card);
        color: var(--pc-green);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .purchase-create .pc-add:hover {
        background: var(--pc-soft);
        border-color: var(--pc-green);
    }

    .purchase-create .pc-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 17px 20px;
        border-top: 1px solid var(--pc-border);
        flex-wrap: wrap;
    }

    .purchase-create .pc-btn {
        min-height: 41px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 17px;
        border-radius: 9px;
        border: 1px solid transparent;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: .2s;
    }

    .purchase-create .pc-btn-primary {
        background: var(--pc-green);
        color: #fff;
    }

    .purchase-create .pc-btn-primary:hover {
        background: var(--pc-green-dark);
    }

    .purchase-create .pc-btn-secondary {
        background: var(--pc-card);
        color: var(--pc-text);
        border-color: var(--pc-border);
    }

    .purchase-create .pc-btn-secondary:hover {
        background: var(--pc-bg);
    }

    .purchase-create .pc-error {
        padding: 13px 15px;
        margin-bottom: 18px;
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 13px;
    }

    .purchase-create .pc-error ul {
        margin: 7px 0 0;
        padding-left: 20px;
    }

    .purchase-create .pc-empty {
        padding: 25px;
        text-align: center;
        color: var(--pc-muted);
        font-size: 13px;
    }

    @media(max-width: 650px) {
        .purchase-create .pc-grid {
            grid-template-columns: 1fr;
        }

        .purchase-create .pc-field.full {
            grid-column: auto;
        }

        .purchase-create .pc-card-body {
            padding: 15px;
        }

        .purchase-create .pc-header {
            align-items: flex-start;
        }

        .purchase-create .pc-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .purchase-create .pc-actions .pc-btn {
            width: 100%;
        }
    }
</style>

<div class="purchase-create">

    <div class="pc-header">
        <div>
            <h2 class="pc-title">New Purchase</h2>
            <p class="pc-subtitle">Record stock received from a supplier.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="pc-error">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
        @csrf

        {{-- Purchase information --}}
        <div class="pc-card">
            <div class="pc-card-header">
                <h3>Purchase Information</h3>
            </div>

            <div class="pc-card-body">
                <div class="pc-grid">

                    <div class="pc-field">
                        <label for="supplier_id">
                            Supplier <span class="pc-required">*</span>
                        </label>

                        <select name="supplier_id" id="supplier_id" required>
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}"
                                    @selected(old('supplier_id') == $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pc-field">
                        <label for="purchase_date">
                            Purchase Date <span class="pc-required">*</span>
                        </label>

                        <input
                            type="date"
                            name="purchase_date"
                            id="purchase_date"
                            value="{{ old('purchase_date', now()->toDateString()) }}"
                            required
                        >
                    </div>

                    <div class="pc-field full">
                        <label for="notes">Notes</label>

                        <textarea
                            name="notes"
                            id="notes"
                            placeholder="Optional purchase notes..."
                        >{{ old('notes') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- Purchase items --}}
        <div class="pc-card">
            <div class="pc-card-header">
                <div>
                    <h3>Purchase Items</h3>
                    <span class="pc-hint">Add each product, quantity and unit cost.</span>
                </div>

                <button type="button" class="pc-add" id="addRow">
                    <span>＋</span> Add Product
                </button>
            </div>

            <div class="pc-table-wrap">
                <table id="purchaseRows">
                    <thead>
                        <tr>
                            <th style="width:45px">#</th>
                            <th>Product</th>
                            <th style="width:150px">Quantity</th>
                            <th style="width:180px">Unit Cost</th>
                            <th style="width:65px">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $oldItems = old('items', [
                                ['product_id' => '', 'quantity' => '', 'unit_cost' => '']
                            ]);
                        @endphp

                        @foreach($oldItems as $index => $item)
                            <tr>
                                <td class="pc-row-number">{{ $loop->iteration }}</td>

                                <td>
                                    <select name="items[{{ $index }}][product_id]" required>
                                        <option value="">Select product</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}"
                                                @selected(($item['product_id'] ?? '') == $p->id)>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][quantity]"
                                        min="1"
                                        step="1"
                                        value="{{ $item['quantity'] ?? '' }}"
                                        placeholder="Quantity"
                                        required
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="items[{{ $index }}][unit_cost]"
                                        min="0"
                                        step="0.01"
                                        value="{{ $item['unit_cost'] ?? '' }}"
                                        placeholder="0.00"
                                        required
                                    >
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="pc-remove"
                                        title="Remove row"
                                        aria-label="Remove row"
                                        onclick="removePurchaseRow(this)"
                                    >×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pc-actions">
                <span class="pc-hint">
                    Make sure the quantities and unit costs are correct before receiving.
                </span>

                <button type="submit" class="pc-btn pc-btn-primary">
                    Receive Purchase →
                </button>
            </div>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.getElementById('addRow');
    const tbody = document.querySelector('#purchaseRows tbody');

    let nextIndex = {{ count($oldItems) }};

    function updateRowNumbers() {
        tbody.querySelectorAll('tr').forEach((row, index) => {
            row.querySelector('.pc-row-number').textContent = index + 1;
        });
    }

    addButton.addEventListener('click', function () {
        const firstRow = tbody.querySelector('tr');

        if (!firstRow) return;

        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('select, input').forEach(function (field) {
            field.name = field.name.replace(
                /items\[\d+\]/,
                `items[${nextIndex}]`
            );

            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });

        tbody.appendChild(newRow);
        nextIndex++;

        updateRowNumbers();

        const productSelect = newRow.querySelector('select');
        if (productSelect) productSelect.focus();
    });

    updateRowNumbers();
});

function removePurchaseRow(button) {
    const tbody = document.querySelector('#purchaseRows tbody');
    const rows = tbody.querySelectorAll('tr');

    if (rows.length <= 1) {
        alert('At least one purchase item is required.');
        return;
    }

    button.closest('tr').remove();

    tbody.querySelectorAll('tr').forEach((row, index) => {
        row.querySelector('.pc-row-number').textContent = index + 1;
    });
}
</script>

@endsection
