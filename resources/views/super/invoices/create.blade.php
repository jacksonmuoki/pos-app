
@extends('super.layout')

@section('title', 'Create Invoice')
@section('breadcrumb', 'Create Invoice')

@section('content')

<style>
.inv-create{max-width:1100px;margin:auto;display:flex;flex-direction:column;gap:18px}
.inv-heading h1{margin:0;font-size:25px;font-weight:800;color:#17243d}
.inv-heading p{margin:6px 0 0;color:#64748b;font-size:13px}
.inv-card{background:#fff;border:1px solid #e8edf3;border-radius:12px;padding:22px}
.inv-card h3{font-size:15px;color:#17243d;margin:0 0 20px;font-weight:800}
.inv-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}
.inv-field{margin-bottom:16px}
.inv-field label{display:block;font-size:12px;font-weight:750;color:#334155;margin-bottom:7px}
.inv-field input,.inv-field select,.inv-field textarea{width:100%;padding:11px 12px;border:1px solid #dce3ec;border-radius:8px;font-size:12px;color:#334155;background:#fff;outline:none}
.inv-field input:focus,.inv-field select:focus,.inv-field textarea:focus{border-color:#128d51}
.inv-table-wrap{overflow-x:auto}
.inv-items{width:100%;min-width:850px;border-collapse:collapse}
.inv-items th{text-align:left;background:#f8fafc;color:#64748b;font-size:10px;padding:12px 9px;text-transform:uppercase}
.inv-items td{padding:9px 5px;border-bottom:1px solid #edf0f4}
.inv-items input{width:100%;min-width:75px;padding:9px;border:1px solid #dce3ec;border-radius:6px;font-size:12px}
.inv-items .item-description{min-width:190px}
.inv-remove{border:0;background:#fee2e2;color:#991b1b;border-radius:6px;padding:8px 10px;cursor:pointer}
.inv-add{border:1px solid #128d51;background:#ecfdf3;color:#128d51;border-radius:7px;padding:10px 13px;font-weight:750;font-size:11px;cursor:pointer;margin-top:13px}
.inv-summary{max-width:390px;margin-left:auto;display:flex;flex-direction:column;gap:12px}
.inv-summary-row{display:flex;justify-content:space-between;gap:15px;font-size:12px;color:#64748b}
.inv-summary-row strong{color:#17243d}
.inv-summary-total{border-top:1px solid #e8edf3;padding-top:14px;font-size:16px;font-weight:850;color:#128d51}
.inv-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.inv-btn{border:0;background:#128d51;color:#fff;padding:12px 18px;border-radius:8px;font-size:12px;font-weight:750;text-decoration:none;cursor:pointer}
.inv-btn:hover{background:#0d7542;color:white}
.inv-btn.secondary{background:#f1f5f9;color:#334155}
.inv-errors{background:#fef2f2;color:#991b1b;padding:14px;border-radius:8px;font-size:12px;margin-bottom:15px}
@media(max-width:650px){.inv-grid{grid-template-columns:1fr;gap:0}.inv-card{padding:16px}.inv-actions{flex-wrap:wrap}}
</style>

<div class="inv-create">
    <div class="inv-heading">
        <h1>Create Invoice</h1>
        <p>Generate a billing invoice for a registered company.</p>
    </div>

    @if($errors->any())
        <div class="inv-errors">
            <strong>Please correct the following errors:</strong>
            <ul style="margin:8px 0 0;padding-left:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('super.invoices.store') }}" id="invoiceForm">
        @csrf

        <div class="inv-card">
            <h3>Invoice Information</h3>

            <div class="inv-grid">
                <div class="inv-field">
                    <label for="tenant_id">Company *</label>
                    <select name="tenant_id" id="tenant_id" required>
                        <option value="">Select company</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->name ?: $tenant->id }} ({{ $tenant->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="inv-field">
                    <label for="subscription_id">Related Subscription</label>
                    <select name="subscription_id" id="subscription_id">
                        <option value="">No subscription linked</option>
                        @foreach($subscriptions as $subscription)
                            <option value="{{ $subscription->id }}"
                                data-tenant="{{ $subscription->tenant_id }}"
                                @selected(old('subscription_id') == $subscription->id)>
                                {{ $subscription->tenant_id }} —
                                {{ $subscription->plan?->name ?? 'Plan' }} —
                                #{{ $subscription->id }}
                            </option>
                        @endforeach
                    </select>
                    <small style="color:#94a3b8;font-size:10px">Only active and trial subscriptions are listed.</small>
                </div>

                <div class="inv-field">
                    <label for="issue_date">Issue Date *</label>
                    <input type="date" name="issue_date" id="issue_date"
                        value="{{ old('issue_date', today()->format('Y-m-d')) }}" required>
                </div>

                <div class="inv-field">
                    <label for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date"
                        value="{{ old('due_date') }}">
                </div>

                <div class="inv-field">
                    <label for="currency">Currency *</label>
                    <select name="currency" id="currency" required>
                        @foreach(['KES','USD','EUR','GBP'] as $currency)
                            <option value="{{ $currency }}" @selected(old('currency', 'KES') === $currency)>
                                {{ $currency }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="inv-field">
                    <label for="notes">Invoice Notes</label>
                    <input type="text" name="notes" id="notes"
                        value="{{ old('notes') }}" placeholder="Optional billing notes">
                </div>
            </div>
        </div>

        <div class="inv-card">
            <h3>Invoice Items</h3>

            <div class="inv-table-wrap">
                <table class="inv-items">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Discount</th>
                            <th>Tax %</th>
                            <th>Line Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="invoiceItems">
                        @php
                            $oldItems = old('items', [
                                ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'discount_amount' => 0, 'tax_rate' => 0]
                            ]);
                        @endphp

                        @foreach($oldItems as $index => $item)
                            <tr class="invoice-item-row">
                                <td><input class="item-description" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Item description" required></td>
                                <td><input type="number" step="0.001" min="0.001" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="item-quantity" required></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" class="item-price" required></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][discount_amount]" value="{{ $item['discount_amount'] ?? 0 }}" class="item-discount"></td>
                                <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][tax_rate]" value="{{ $item['tax_rate'] ?? 0 }}" class="item-tax"></td>
                                <td><strong class="item-total">0.00</strong></td>
                                <td><button type="button" class="inv-remove" onclick="removeInvoiceItem(this)">×</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="button" class="inv-add" id="addInvoiceItem">+ Add Invoice Item</button>

            <div class="inv-summary" style="margin-top:25px">
                <div class="inv-summary-row">
                    <span>Subtotal</span>
                    <strong id="subtotalDisplay">0.00</strong>
                </div>
                <div class="inv-summary-row">
                    <span>Discount</span>
                    <strong id="discountDisplay">0.00</strong>
                </div>
                <div class="inv-summary-row">
                    <span>Tax</span>
                    <strong id="taxDisplay">0.00</strong>
                </div>
                <div class="inv-summary-row inv-summary-total">
                    <span>Grand Total</span>
                    <strong id="grandTotalDisplay">0.00</strong>
                </div>
            </div>
        </div>

        <div class="inv-actions">
            <a href="{{ route('super.invoices.index') }}" class="inv-btn secondary">Cancel</a>
            <button type="submit" class="inv-btn">Create Invoice</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('invoiceItems');
    const currency = document.getElementById('currency');
    let nextIndex = {{ count($oldItems) }};

    function money(value) {
        return Number(value || 0).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function recalculate() {
        let subtotal = 0;
        let discounts = 0;
        let taxes = 0;
        let total = 0;

        tbody.querySelectorAll('.invoice-item-row').forEach(row => {
            const quantity = Math.max(0, Number(row.querySelector('.item-quantity').value) || 0);
            const price = Math.max(0, Number(row.querySelector('.item-price').value) || 0);
            const discount = Math.max(0, Number(row.querySelector('.item-discount').value) || 0);
            const taxRate = Math.max(0, Number(row.querySelector('.item-tax').value) || 0);

            const gross = quantity * price;
            const validDiscount = Math.min(discount, gross);
            const taxable = Math.max(0, gross - validDiscount);
            const tax = taxable * taxRate / 100;
            const line = taxable + tax;

            subtotal += gross;
            discounts += validDiscount;
            taxes += tax;
            total += line;

            row.querySelector('.item-total').textContent = money(line);
        });

        const code = currency.value;

        document.getElementById('subtotalDisplay').textContent = code + ' ' + money(subtotal);
        document.getElementById('discountDisplay').textContent = code + ' ' + money(discounts);
        document.getElementById('taxDisplay').textContent = code + ' ' + money(taxes);
        document.getElementById('grandTotalDisplay').textContent = code + ' ' + money(total);
    }

    function addRow() {
        const index = nextIndex++;

        const row = document.createElement('tr');
        row.className = 'invoice-item-row';

        row.innerHTML = `
            <td><input class="item-description" name="items[${index}][description]" placeholder="Item description" required></td>
            <td><input type="number" step="0.001" min="0.001" name="items[${index}][quantity]" value="1" class="item-quantity" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][unit_price]" value="0" class="item-price" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${index}][discount_amount]" value="0" class="item-discount"></td>
            <td><input type="number" step="0.01" min="0" max="100" name="items[${index}][tax_rate]" value="0" class="item-tax"></td>
            <td><strong class="item-total">0.00</strong></td>
            <td><button type="button" class="inv-remove" onclick="removeInvoiceItem(this)">×</button></td>
        `;

        tbody.appendChild(row);
        recalculate();
    }

    window.removeInvoiceItem = function (button) {
        const rows = tbody.querySelectorAll('.invoice-item-row');

        if (rows.length <= 1) {
            alert('An invoice must have at least one item.');
            return;
        }

        button.closest('tr').remove();
        recalculate();
    };

    document.getElementById('addInvoiceItem').addEventListener('click', addRow);

    tbody.addEventListener('input', recalculate);
    currency.addEventListener('change', recalculate);

    const tenantSelect = document.getElementById('tenant_id');
    const subscriptionSelect = document.getElementById('subscription_id');

    function filterSubscriptions() {
        const tenantId = tenantSelect.value;

        Array.from(subscriptionSelect.options).forEach((option, index) => {
            if (index === 0) return;

            const matches = !tenantId || option.dataset.tenant === tenantId;
            option.hidden = !matches;

            if (!matches && option.selected) {
                subscriptionSelect.value = '';
            }
        });
    }

    tenantSelect.addEventListener('change', filterSubscriptions);

    filterSubscriptions();
    recalculate();
});
</script>

@endsection
