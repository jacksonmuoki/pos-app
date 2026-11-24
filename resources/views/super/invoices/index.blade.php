
@extends('super.layout')

@section('title', 'Invoices')
@section('breadcrumb', 'Invoice Management')

@section('content')

<style>
.invoice-page{display:flex;flex-direction:column;gap:20px}
.invoice-head{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}
.invoice-head h1{margin:0;font-size:25px;font-weight:800;color:#17243d}
.invoice-head p{margin:5px 0 0;color:#64748b;font-size:13px}
.invoice-btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border:0;border-radius:8px;background:#128d51;color:#fff;text-decoration:none;font-size:12px;font-weight:750;cursor:pointer}
.invoice-btn:hover{background:#0d7542;color:#fff}
.invoice-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.invoice-stat{background:#fff;border:1px solid #e8edf3;border-radius:12px;padding:18px}
.invoice-stat span{font-size:12px;color:#64748b;font-weight:650}
.invoice-stat h2{font-size:24px;color:#17243d;margin:9px 0 0;font-weight:850}
.invoice-card{background:#fff;border:1px solid #e8edf3;border-radius:12px;overflow:hidden}
.invoice-card-head{padding:18px 20px;border-bottom:1px solid #edf0f4;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.invoice-card-head h3{margin:0;font-size:15px;font-weight:800;color:#17243d}
.invoice-filters{display:flex;gap:8px;flex-wrap:wrap}
.invoice-input{border:1px solid #dce3ec;border-radius:8px;padding:10px 11px;font-size:12px;background:#fff;color:#334155;max-width:100%}
.invoice-table-wrap{overflow-x:auto}
.invoice-table{width:100%;min-width:850px;border-collapse:collapse}
.invoice-table th{background:#f8fafc;padding:13px 16px;text-align:left;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.5px}
.invoice-table td{padding:15px 16px;border-top:1px solid #edf0f4;font-size:12px;color:#334155}
.invoice-table a{color:#128d51;text-decoration:none;font-weight:750}
.invoice-number{font-weight:800;color:#17243d}
.invoice-muted{font-size:11px;color:#94a3b8;margin-top:4px}
.invoice-status{display:inline-block;padding:5px 9px;border-radius:30px;font-size:10px;font-weight:800;text-transform:capitalize}
.invoice-status.paid{background:#dcfce7;color:#166534}
.invoice-status.issued{background:#dbeafe;color:#1d4ed8}
.invoice-status.partial{background:#fef3c7;color:#92400e}
.invoice-status.overdue{background:#fee2e2;color:#991b1b}
.invoice-status.draft{background:#f1f5f9;color:#475569}
.invoice-status.cancelled{background:#f1f5f9;color:#64748b}
.invoice-empty{text-align:center;padding:45px 15px;color:#94a3b8;font-size:12px}
.invoice-empty strong{display:block;color:#334155;margin-bottom:7px}
.invoice-pagination{padding:16px 20px}
@media(max-width:750px){.invoice-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:480px){.invoice-stats{grid-template-columns:1fr 1fr;gap:8px}.invoice-stat{padding:13px}.invoice-stat h2{font-size:19px}}
</style>

<div class="invoice-page">

    <div class="invoice-head">
        <div>
            <h1>Invoices & Billing</h1>
            <p>Manage company invoices, payments and outstanding balances.</p>
        </div>

        <a href="{{ route('super.invoices.create') }}" class="invoice-btn">
            + Create Invoice
        </a>
    </div>

    <div class="invoice-stats">
        <div class="invoice-stat">
            <span>Total Invoices</span>
            <h2>{{ number_format($totalInvoices) }}</h2>
        </div>

        <div class="invoice-stat">
            <span>Total Billed</span>
            <h2>KES {{ number_format((float)$totalBilled, 2) }}</h2>
        </div>

        <div class="invoice-stat">
            <span>Payments Collected</span>
            <h2>KES {{ number_format((float)$totalCollected, 2) }}</h2>
        </div>

        <div class="invoice-stat">
            <span>Outstanding Balance</span>
            <h2>KES {{ number_format((float)$totalOutstanding, 2) }}</h2>
        </div>

        <div class="invoice-stat">
            <span>Overdue Invoices</span>
            <h2>{{ number_format($overdueCount) }}</h2>
        </div>
    </div>

    <div class="invoice-card">
        <div class="invoice-card-head">
            <h3>All Invoices</h3>

            <form method="GET" action="{{ route('super.invoices.index') }}" class="invoice-filters">
                <input class="invoice-input" type="text" name="search"
                    value="{{ request('search') }}" placeholder="Invoice number / company">

                <select class="invoice-input" name="status">
                    <option value="">All statuses</option>
                    @foreach(['draft','issued','partial','paid','overdue','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>

                <input class="invoice-input" type="text" name="tenant_id"
                    value="{{ request('tenant_id') }}" placeholder="Company ID">

                <input class="invoice-input" type="date" name="from" value="{{ request('from') }}">
                <input class="invoice-input" type="date" name="to" value="{{ request('to') }}">

                <button class="invoice-btn" type="submit">Filter</button>
                <a class="invoice-btn" style="background:#f1f5f9;color:#334155"
                   href="{{ route('super.invoices.index') }}">Reset</a>
            </form>
        </div>

        <div class="invoice-table-wrap">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Company</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                                <div class="invoice-muted">#{{ $invoice->id }}</div>
                            </td>
                            <td>
                                <strong>{{ $invoice->tenant?->name ?? $invoice->tenant_id }}</strong>
                                <div class="invoice-muted">{{ $invoice->tenant_id }}</div>
                            </td>
                            <td>{{ $invoice->issue_date?->format('d M Y') }}</td>
                            <td>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</td>
                            <td><strong>{{ $invoice->currency }} {{ number_format((float)$invoice->total, 2) }}</strong></td>
                            <td>{{ $invoice->currency }} {{ number_format((float)$invoice->amount_paid, 2) }}</td>
                            <td>{{ $invoice->currency }} {{ number_format((float)$invoice->balance_due, 2) }}</td>
                            <td>
                                <span class="invoice-status {{ $invoice->status }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('super.invoices.show', $invoice) }}">View invoice</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="invoice-empty">
                                    <strong>No invoices found</strong>
                                    Create an invoice to start tracking company billing.
                                    <br><br>
                                    <a href="{{ route('super.invoices.create') }}" class="invoice-btn">
                                        Create First Invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="invoice-pagination">
            {{ $invoices->links() }}
        </div>
    </div>
</div>

@endsection
