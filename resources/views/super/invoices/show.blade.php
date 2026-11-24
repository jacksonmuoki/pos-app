
@extends('super.layout')

@section('title', 'Invoice Details')
@section('breadcrumb', 'Invoice Details')

@section('content')

<style>
.inv-detail{display:flex;flex-direction:column;gap:18px}
.inv-detail-head{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}
.inv-detail-head h1{margin:0;color:#17243d;font-size:25px;font-weight:800}
.inv-detail-head p{margin:5px 0 0;color:#64748b;font-size:12px}
.inv-detail-actions{display:flex;gap:8px;flex-wrap:wrap}
.inv-detail-btn{display:inline-flex;padding:10px 14px;border:0;border-radius:8px;background:#128d51;color:white;text-decoration:none;font-size:11px;font-weight:750;cursor:pointer}
.inv-detail-btn:hover{color:white;background:#0d7542}
.inv-detail-btn.light{background:#f1f5f9;color:#334155}
.inv-detail-btn.danger{background:#dc2626}
.inv-detail-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.inv-detail-stat{background:white;border:1px solid #e8edf3;border-radius:11px;padding:16px}
.inv-detail-stat span{font-size:11px;color:#64748b}
.inv-detail-stat strong{display:block;font-size:19px;color:#17243d;margin-top:8px;overflow-wrap:anywhere}
.inv-detail-card{background:white;border:1px solid #e8edf3;border-radius:12px;padding:21px}
.inv-detail-card h3{font-size:15px;color:#17243d;margin:0 0 17px;font-weight:800}
.inv-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.inv-meta span{display:block;color:#94a3b8;font-size:10px;margin-bottom:5px}
.inv-meta strong{color:#334155;font-size:12px;overflow-wrap:anywhere}
.inv-detail-table{width:100%;border-collapse:collapse;min-width:650px}
.inv-detail-table th{text-align:left;background:#f8fafc;color:#64748b;font-size:10px;padding:12px}
.inv-detail-table td{padding:13px 12px;border-bottom:1px solid #edf0f4;color:#334155;font-size:12px}
.inv-detail-table-wrap{overflow-x:auto}
.inv-status{display:inline-block;padding:5px 10px;border-radius:30px;font-size:10px;font-weight:800;text-transform:capitalize}
.inv-status.paid{background:#dcfce7;color:#166534}
.inv-status.issued{background:#dbeafe;color:#1d4ed8}
.inv-status.partial{background:#fef3c7;color:#92400e}
.inv-status.overdue{background:#fee2e2;color:#991b1b}
.inv-status.cancelled{background:#f1f5f9;color:#64748b}
.inv-status.draft{background:#f1f5f9;color:#475569}
.inv-payment-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.inv-payment-form label{display:block;font-size:11px;font-weight:750;color:#334155;margin-bottom:6px}
.inv-payment-form input,.inv-payment-form select,.inv-payment-form textarea{width:100%;padding:11px;border:1px solid #dce3ec;border-radius:8px;font-size:12px}
.inv-alert{padding:12px 15px;border-radius:8px;font-size:12px}
@media(max-width:800px){.inv-detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.inv-meta{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.inv-detail-grid{grid-template-columns:1fr 1fr}.inv-detail-stat strong{font-size:16px}.inv-meta{grid-template-columns:1fr}.inv-payment-form{grid-template-columns:1fr}}
</style>

<div class="inv-detail">

    <div class="inv-detail-head">
        <div>
            <h1>{{ $invoice->invoice_number }}</h1>
            <p>Invoice details and payment management</p>
        </div>

        <div class="inv-detail-actions">
            <a href="{{ route('super.invoices.index') }}" class="inv-detail-btn light">Back</a>
            <a href="{{ route('super.invoices.print', $invoice) }}" target="_blank" class="inv-detail-btn light">Print</a>
            <a href="{{ route('super.invoices.download', $invoice) }}" class="inv-detail-btn">Download</a>
        </div>
    </div>

    @if(session('status'))
        <div class="inv-alert" style="background:#ecfdf3;color:#166534">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="inv-alert" style="background:#fef2f2;color:#991b1b">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="inv-detail-grid">
        <div class="inv-detail-stat">
            <span>Invoice Total</span>
            <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->total, 2) }}</strong>
        </div>
        <div class="inv-detail-stat">
            <span>Amount Paid</span>
            <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->amount_paid, 2) }}</strong>
        </div>
        <div class="inv-detail-stat">
            <span>Balance Due</span>
            <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->balance_due, 2) }}</strong>
        </div>
        <div class="inv-detail-stat">
            <span>Invoice Status</span>
            <strong><span class="inv-status {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></strong>
        </div>
    </div>

    <div class="inv-detail-card">
        <h3>Invoice Information</h3>

        <div class="inv-meta">
            <div>
                <span>Invoice Number</span>
                <strong>{{ $invoice->invoice_number }}</strong>
            </div>
            <div>
                <span>Company</span>
                <strong>{{ $invoice->tenant?->name ?? $invoice->tenant_id }}</strong>
            </div>
            <div>
                <span>Company ID</span>
                <strong>{{ $invoice->tenant_id }}</strong>
            </div>
            <div>
                <span>Issue Date</span>
                <strong>{{ $invoice->issue_date?->format('d M Y') }}</strong>
            </div>
            <div>
                <span>Due Date</span>
                <strong>{{ $invoice->due_date?->format('d M Y') ?? 'Not specified' }}</strong>
            </div>
            <div>
                <span>Subscription</span>
                <strong>
                    {{ $invoice->subscription?->plan?->name ?? 'No subscription linked' }}
                </strong>
            </div>
        </div>
    </div>

    <div class="inv-detail-card">
        <h3>Invoice Items</h3>

        <div class="inv-detail-table-wrap">
            <table class="inv-detail-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td><strong>{{ $item->description }}</strong></td>
                            <td>{{ number_format((float)$item->quantity, 3) }}</td>
                            <td>{{ $invoice->currency }} {{ number_format((float)$item->unit_price, 2) }}</td>
                            <td>{{ $invoice->currency }} {{ number_format((float)$item->discount_amount, 2) }}</td>
                            <td>{{ number_format((float)$item->tax_rate, 2) }}%</td>
                            <td><strong>{{ $invoice->currency }} {{ number_format((float)$item->line_total, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="max-width:350px;margin:20px 0 0 auto">
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:12px">
                <span>Subtotal</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->subtotal, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:12px">
                <span>Discount</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->discount_amount, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:12px">
                <span>Tax</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->tax_amount, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:1px solid #e8edf3;font-size:16px;color:#128d51">
                <strong>Total</strong>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->total, 2) }}</strong>
            </div>
        </div>

        @if($invoice->notes)
            <div style="margin-top:15px;padding:13px;background:#f8fafc;border-radius:8px">
                <strong style="font-size:11px;color:#334155">Notes</strong>
                <p style="font-size:12px;color:#64748b;margin:6px 0 0">{{ $invoice->notes }}</p>
            </div>
        @endif
    </div>

    <div class="inv-detail-card">
        <h3>Payment History</h3>

        <div class="inv-detail-table-wrap">
            <table class="inv-detail-table">
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>{{ $payment->payment_reference ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>{{ $payment->currency }} {{ number_format((float)$payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;color:#94a3b8">No payment records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(!in_array($invoice->status, ['paid','cancelled','draft'], true) && (float)$invoice->balance_due > 0)
        <div class="inv-detail-card">
            <h3>Record Payment</h3>

            <p style="font-size:12px;color:#64748b;margin:-8px 0 18px">
                Record a payment only after confirming that funds have been received.
            </p>

            <form method="POST" action="{{ route('super.invoices.mark-paid', $invoice) }}">
                @csrf

                <div class="inv-payment-form">
                    <div>
                        <label>Payment Amount *</label>
                        <input type="number" name="amount" min="0.01" max="{{ $invoice->balance_due }}"
                            step="0.01" value="{{ number_format((float)$invoice->balance_due, 2, '.', '') }}" required>
                    </div>

                    <div>
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label>Payment Reference</label>
                        <input type="text" name="payment_reference" maxlength="150"
                            placeholder="M-Pesa code or transaction reference">
                    </div>

                    <div>
                        <label>Notes</label>
                        <input type="text" name="notes" maxlength="1000" placeholder="Optional payment notes">
                    </div>
                </div>

                <button type="submit" class="inv-detail-btn" style="margin-top:18px">
                    Record Confirmed Payment
                </button>
            </form>
        </div>
    @endif

    @if(!in_array($invoice->status, ['cancelled','paid'], true) && (float)$invoice->amount_paid <= 0)
        <div class="inv-detail-card">
            <h3>Cancel Invoice</h3>

            <p style="font-size:12px;color:#64748b">
                An invoice can only be cancelled if no payments have been recorded.
            </p>

            <form method="POST" action="{{ route('super.invoices.cancel', $invoice) }}"
                onsubmit="return confirm('Are you sure you want to cancel this invoice?')">
                @csrf

                <input type="text" name="reason" required maxlength="500"
                    placeholder="Reason for cancellation"
                    style="width:100%;max-width:450px;padding:11px;border:1px solid #dce3ec;border-radius:8px;font-size:12px">

                <button type="submit" class="inv-detail-btn danger" style="margin-left:8px">
                    Cancel Invoice
                </button>
            </form>
        </div>
    @endif

</div>

@endsection
