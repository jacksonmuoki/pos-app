
@extends('layouts.app')

@section('title', 'New Stock Adjustment')
@section('page-heading', 'New Stock Adjustment')

@section('content')

@php
    $products = $products ?? collect();
    $reasons = ['damaged', 'expired', 'lost', 'found', 'recount', 'other'];
@endphp

<style>
    .new-adjustment {
        --na-bg: #fff;
        --na-soft: #f5f8f6;
        --na-text: #17251d;
        --na-muted: #718078;
        --na-border: #e4ebe6;
        --na-green: #15803d;
        --na-green-soft: #eff7f1;
        color: var(--na-text);
    }

    [data-theme="dark"] .new-adjustment,
    .dark .new-adjustment {
        --na-bg: #17231b;
        --na-soft: #1d3325;
        --na-text: #e5eee8;
        --na-muted: #9aaba0;
        --na-border: #2a3a30;
        --na-green: #4ade80;
        --na-green-soft: #1d3325;
    }

    .new-adjustment .na-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .new-adjustment .na-heading h2 {
        margin: 0 0 5px;
        color: var(--na-text);
        font-size: 23px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .new-adjustment .na-heading p {
        margin: 0;
        color: var(--na-muted);
        font-size: 13px;
    }

    .new-adjustment .na-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 13px;
        border: 1px solid var(--na-border);
        border-radius: 9px;
        color: var(--na-text);
        background: var(--na-bg);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }

    .new-adjustment .na-back:hover {
        background: var(--na-soft);
    }

    .new-adjustment .na-card {
        max-width: 850px;
        padding: 24px;
        background: var(--na-bg);
        border: 1px solid var(--na-border);
        border-radius: 15px;
    }

    .new-adjustment .na-card-heading {
        display: flex;
        align-items: center;
        gap: 13px;
        padding-bottom: 20px;
        margin-bottom: 22px;
        border-bottom: 1px solid var(--na-border);
    }

    .new-adjustment .na-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: 12px;
        background: var(--na-green-soft);
        color: var(--na-green);
        font-size: 21px;
    }

    .new-adjustment .na-card-heading h3 {
        margin: 0 0 4px;
        color: var(--na-text);
        font-size: 15px;
        font-weight: 700;
    }

    .new-adjustment .na-card-heading p {
        margin: 0;
        color: var(--na-muted);
        font-size: 12px;
    }

    .new-adjustment .na-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .new-adjustment .na-field {
        min-width: 0;
    }

    .new-adjustment .na-field-full {
        grid-column: 1 / -1;
    }

    .new-adjustment label {
        display: block;
        margin-bottom: 7px;
        color: var(--na-text);
        font-size: 12px;
        font-weight: 650;
    }

    .new-adjustment .na-required {
        color: #dc2626;
    }

    .new-adjustment input,
    .new-adjustment select {
        display: block;
        width: 100%;
        min-height: 43px;
        padding: 10px 12px;
        border: 1px solid var(--na-border);
        border-radius: 9px;
        outline: none;
        background: var(--na-bg);
        color: var(--na-text);
        font-family: inherit;
        font-size: 13px;
        transition: border-color .2s, box-shadow .2s;
    }

    .new-adjustment input:focus,
    .new-adjustment select:focus {
        border-color: var(--na-green);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--na-green) 13%, transparent);
    }

    .new-adjustment input::placeholder {
        color: var(--na-muted);
    }

    .new-adjustment .na-help {
        margin-top: 6px;
        color: var(--na-muted);
        font-size: 11px;
        line-height: 1.5;
    }

    .new-adjustment .na-error {
        margin-top: 6px;
        color: #dc2626;
        font-size: 12px;
    }

    .new-adjustment .na-input-error {
        border-color: #dc2626;
    }

    .new-adjustment .na-info {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 13px 14px;
        margin-top: 21px;
        border: 1px solid var(--na-border);
        border-radius: 10px;
        background: var(--na-soft);
        color: var(--na-muted);
        font-size: 12px;
        line-height: 1.6;
    }

    .new-adjustment .na-info strong {
        color: var(--na-text);
    }

    .new-adjustment .na-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 21px;
        margin-top: 22px;
        border-top: 1px solid var(--na-border);
    }

    .new-adjustment .na-cancel,
    .new-adjustment .na-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 41px;
        padding: 10px 16px;
        border-radius: 9px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 650;
        text-decoration: none;
        cursor: pointer;
    }

    .new-adjustment .na-cancel {
        border: 1px solid var(--na-border);
        background: var(--na-bg);
        color: var(--na-text);
    }

    .new-adjustment .na-submit {
        border: 1px solid var(--na-green);
        background: var(--na-green);
        color: #fff;
    }

    .new-adjustment .na-submit:hover {
        filter: brightness(.94);
    }

    @media (max-width: 600px) {
        .new-adjustment .na-card {
            padding: 17px;
        }

        .new-adjustment .na-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .new-adjustment .na-field-full {
            grid-column: auto;
        }

        .new-adjustment .na-actions {
            flex-direction: column-reverse;
        }

        .new-adjustment .na-actions a,
        .new-adjustment .na-actions button {
            width: 100%;
        }
    }
</style>

<div class="new-adjustment">

    <div class="na-header">
        <div class="na-heading">
            <h2>New Stock Adjustment</h2>
            <p>Record changes to your product inventory.</p>
        </div>

        <a href="{{ route('adjustments.index') }}" class="na-back">
            <span>←</span> Back to adjustments
        </a>
    </div>

    <div class="na-card">

        <div class="na-card-heading">
            <div class="na-icon">↕</div>
            <div>
                <h3>Adjustment information</h3>
                <p>Enter the product, adjustment type and reason.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('adjustments.store') }}">
            @csrf

            <div class="na-grid">

                <div class="na-field na-field-full">
                    <label for="product_id">
                        Product <span class="na-required">*</span>
                    </label>

                    <select
                        name="product_id"
                        id="product_id"
                        required
                        class="@error('product_id') na-input-error @enderror"
                    >
                        <option value="">Select a product</option>

                        @foreach($products as $p)
                            <option
                                value="{{ $p->id }}"
                                @selected((string) old('product_id') === (string) $p->id)
                            >
                                {{ $p->name }} — Current stock: {{ $p->stock }}
                            </option>
                        @endforeach
                    </select>

                    @error('product_id')
                        <div class="na-error">{{ $message }}</div>
                    @enderror

                    <div class="na-help">
                        Choose the product whose stock you want to adjust.
                    </div>
                </div>

                <div class="na-field">
                    <label for="type">
                        Adjustment type <span class="na-required">*</span>
                    </label>

                    <select name="type" id="type" required>
                        <option value="add" @selected(old('type', 'add') === 'add')>
                            Add stock
                        </option>
                        <option value="remove" @selected(old('type') === 'remove')>
                            Remove stock
                        </option>
                        <option value="set" @selected(old('type') === 'set')>
                            Set exact stock count
                        </option>
                    </select>

                    <div class="na-help">
                        Choose whether to increase, decrease or replace the stock count.
                    </div>
                </div>

                <div class="na-field">
                    <label for="quantity">
                        Quantity <span class="na-required">*</span>
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        min="0"
                        step="1"
                        value="{{ old('quantity') }}"
                        placeholder="Enter quantity"
                        required
                        class="@error('quantity') na-input-error @enderror"
                    >

                    @error('quantity')
                        <div class="na-error">{{ $message }}</div>
                    @enderror

                    <div class="na-help">
                        Enter the quantity to add, remove or set.
                    </div>
                </div>

                <div class="na-field na-field-full">
                    <label for="reason">
                        Reason for adjustment <span class="na-required">*</span>
                    </label>

                    <select name="reason" id="reason" required>
                        @foreach($reasons as $r)
                            <option
                                value="{{ $r }}"
                                @selected(old('reason', 'other') === $r)
                            >
                                {{ ucfirst($r) }}
                            </option>
                        @endforeach
                    </select>

                    @error('reason')
                        <div class="na-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="na-field na-field-full">
                    <label for="notes">Additional notes</label>

                    <input
                        type="text"
                        name="notes"
                        id="notes"
                        value="{{ old('notes') }}"
                        placeholder="Add any useful explanation (optional)"
                        maxlength="1000"
                        class="@error('notes') na-input-error @enderror"
                    >

                    @error('notes')
                        <div class="na-error">{{ $message }}</div>
                    @enderror

                    <div class="na-help">
                        Optional details to explain this stock adjustment.
                    </div>
                </div>

            </div>

            <div class="na-info">
                <span>ⓘ</span>
                <div>
                    <strong>Important:</strong>
                    Stock adjustments affect your inventory records.
                    Verify the selected product and quantity before saving.
                </div>
            </div>

            <div class="na-actions">
                <a href="{{ route('adjustments.index') }}" class="na-cancel">
                    Cancel
                </a>

                <button type="submit" class="na-submit">
                    Save adjustment
                </button>
            </div>

        </form>
    </div>
</div>

@endsection
