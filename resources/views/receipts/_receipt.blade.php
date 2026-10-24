<div class="receipt">
    <h3 style="text-align:center;margin:0">{{ $companyName }}</h3>
    <p style="text-align:center;margin:4px 0 12px">
        Receipt {{ $order->receipt_no }}<br>
        {{ $order->created_at->format('d M Y H:i') }}
    </p>

    <p>
        Cashier: {{ $order->user->name ?? '-' }}<br>
        Customer: {{ $order->customer->name ?? 'Walk-in' }}
    </p>

    <table style="width:100%">
        <thead>
            <tr><th align="left">Item</th><th align="center">Qty</th><th align="right">Price</th><th align="right">Amount</th></tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td align="center">{{ $item->quantity }}</td>
                <td align="right">{{ number_format($item->unit_price, 2) }}</td>
                <td align="right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table style="width:100%;margin-top:10px">
        <tr><td>Subtotal</td><td align="right">{{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td>Discount</td><td align="right">-{{ number_format($order->discount, 2) }}</td></tr>
        <tr><td>Tax</td><td align="right">{{ number_format($order->tax, 2) }}</td></tr>
        <tr><th align="left">Total</th><th align="right">{{ number_format($order->total, 2) }}</th></tr>
    </table>

    <p style="margin-top:12px"><strong>Payment</strong></p>
    @foreach($order->payments as $p)
        <p style="margin:2px 0">
            {{ strtoupper($p->method) }} {{ number_format($p->amount, 2) }}
            ({{ ucfirst($p->status) }})
            @if($p->provider_reference) Ref: {{ $p->provider_reference }} @endif
        </p>
    @endforeach

    <p style="text-align:center;margin-top:16px">Thank you for your business.</p>
</div>