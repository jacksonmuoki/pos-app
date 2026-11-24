
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }}</title>

    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#17243d}
        .print-actions{max-width:850px;margin:20px auto;display:flex;justify-content:flex-end;gap:10px}
        .print-actions button{padding:11px 18px;border:0;border-radius:7px;background:#128d51;color:white;font-weight:700;cursor:pointer}
        .print-actions a{padding:11px 18px;border-radius:7px;background:#e2e8f0;color:#334155;text-decoration:none;font-size:13px}
        .invoice-paper{max-width:850px;margin:0 auto 35px;background:#fff;padding:55px;box-shadow:0 5px 25px rgba(15,23,42,.06)}
        .invoice-top{display:flex;justify-content:space-between;gap:20px;padding-bottom:30px;border-bottom:2px solid #128d51}
        .invoice-brand h1{font-size:28px;color:#128d51;margin:0 0 8px}
        .invoice-brand p{font-size:12px;color:#64748b;margin:0}
        .invoice-title{text-align:right}
        .invoice-title h2{font-size:30px;margin:0 0 8px;letter-spacing:1px}
        .invoice-title p{font-size:12px;color:#64748b;margin:4px 0}
        .invoice-parties{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin:35px 0}
        .invoice-parties h4{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px}
        .invoice-parties strong{font-size:15px}
        .invoice-parties p{font-size:12px;color:#64748b;line-height:1.7;margin:5px 0}
        .invoice-table{width:100%;border-collapse:collapse;margin-top:20px}
        .invoice-table th{text-align:left;padding:13px 10px;background:#f1f5f9;color:#475569;font-size:10px;text-transform:uppercase}
        .invoice-table td{padding:14px 10px;border-bottom:1px solid #e8edf3;font-size:12px}
        .invoice-table .right{text-align:right}
        .invoice-summary{width:310px;margin:25px 0 0 auto}
        .summary-row{display:flex;justify-content:space-between;padding:8px 0;font-size:12px;color:#64748b}
        .summary-row.total{border-top:2px solid #128d51;margin-top:8px;padding-top:15px;font-size:17px;font-weight:800;color:#128d51}
        .invoice-footer{border-top:1px solid #e8edf3;margin-top:45px;padding-top:20px;text-align:center}
        .invoice-footer strong{font-size:12px;color:#334155}
        .invoice-footer p{font-size:11px;color:#94a3b8;line-height:1.6}
        .invoice-status{display:inline-block;padding:6px 12px;background:#ecfdf3;color:#166534;border-radius:30px;font-size:10px;font-weight:800;text-transform:uppercase}
        @media(max-width:600px){.invoice-paper{padding:22px;margin:0 10px 20px}.invoice-top{flex-direction:column}.invoice-title{text-align:left}.invoice-parties{grid-template-columns:1fr}.invoice-summary{width:100%}.invoice-table{min-width:580px}.table-scroll{overflow-x:auto}}
        @media print{
            body{background:white}
            .print-actions{display:none}
            .invoice-paper{max-width:none;margin:0;padding:35px;box-shadow:none}
            @page{size:A4;margin:12mm}
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <a href="{{ route('super.invoices.show', $invoice) }}">Back</a>
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="invoice-paper">

        <div class="invoice-top">
            <div class="invoice-brand">
                <h1>POS PLATFORM</h1>
                <p>Software billing and subscription services</p>
            </div>

            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p><strong>{{ $invoice->invoice_number }}</strong></p>
                <p>Issue date: {{ $invoice->issue_date?->format('d M Y') }}</p>
                <p>Due date: {{ $invoice->due_date?->format('d M Y') ?? 'Not specified' }}</p>
                <p><span class="invoice-status">{{ $invoice->status }}</span></p>
            </div>
        </div>

        <div class="invoice-parties">
            <div>
                <h4>Billed To</h4>
                <strong>{{ $invoice->tenant?->name ?? $invoice->tenant_id }}</strong>
                <p>
                    Company ID: {{ $invoice->tenant_id }}<br>
                    @if($invoice->tenant?->contact_email)
                        Email: {{ $invoice->tenant->contact_email }}<br>
                    @endif
                    @if($invoice->tenant?->contact_phone)
                        Phone: {{ $invoice->tenant->contact_phone }}
                    @endif
                </p>
            </div>

            <div>
                <h4>Invoice Details</h4>
                <p>
                    Invoice number: {{ $invoice->invoice_number }}<br>
                    Currency: {{ $invoice->currency }}<br>
                    Billing plan: {{ $invoice->subscription?->plan?->name ?? 'General billing' }}<br>
                    Created: {{ $invoice->created_at?->format('d M Y') }}
                </p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="right">Quantity</th>
                        <th class="right">Unit Price</th>
                        <th class="right">Discount</th>
                        <th class="right">Tax</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="right">{{ number_format((float)$item->quantity, 3) }}</td>
                            <td class="right">{{ number_format((float)$item->unit_price, 2) }}</td>
                            <td class="right">{{ number_format((float)$item->discount_amount, 2) }}</td>
                            <td class="right">{{ number_format((float)$item->tax_rate, 2) }}%</td>
                            <td class="right"><strong>{{ number_format((float)$item->line_total, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="invoice-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->subtotal, 2) }}</strong>
            </div>

            <div class="summary-row">
                <span>Discount</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->discount_amount, 2) }}</strong>
            </div>

            <div class="summary-row">
                <span>Tax</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->tax_amount, 2) }}</strong>
            </div>

            <div class="summary-row total">
                <span>Total</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->total, 2) }}</strong>
            </div>

            <div class="summary-row">
                <span>Amount Paid</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->amount_paid, 2) }}</strong>
            </div>

            <div class="summary-row">
                <span>Balance Due</span>
                <strong>{{ $invoice->currency }} {{ number_format((float)$invoice->balance_due, 2) }}</strong>
            </div>
        </div>

        @if($invoice->notes)
            <div style="margin-top:30px">
                <h4 style="font-size:11px;color:#475569">NOTES</h4>
                <p style="font-size:12px;color:#64748b;line-height:1.6">
                    {{ $invoice->notes }}
                </p>
            </div>
        @endif

        <div class="invoice-footer">
            <strong>Thank you for your business.</strong>
            <p>
                This invoice was generated electronically by the POS Platform.<br>
                Please quote the invoice number when making payment.
            </p>
        </div>

    </main>
</body>
</html>
