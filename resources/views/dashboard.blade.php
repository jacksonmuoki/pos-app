@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@push('styles')
<style>
.db { display: grid; gap: 18px; }
.db * { box-sizing: border-box; }
.db-top { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.db-eyebrow { font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--primary); }
.db-top h1 { margin: 4px 0; font-size: 26px; font-weight: 800; letter-spacing: -.03em; }
.db-top p { margin: 0; font-size: 13px; color: var(--muted); }
.db-row-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.db-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 13px; border-radius: 9px; border: 1px solid var(--border); background: var(--surface); font-size: 12px; font-weight: 650; text-decoration: none; color: inherit; }
.db-btn:hover { border-color: var(--primary); }
.db-btn.primary { background: var(--primary); border-color: var(--primary); color: #fff; }
.db-tabs { display: inline-flex; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: var(--surface); }
.db-tabs a { padding: 8px 13px; font-size: 12px; font-weight: 650; color: var(--muted); text-decoration: none; }
.db-tabs a.on { background: var(--primary-light); color: var(--primary); }

.db-alerts { display: grid; gap: 8px; }
.db-alert { padding: 10px 14px; border-radius: 10px; font-size: 12px; font-weight: 600; border: 1px solid var(--border); display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center; }
.db-alert a { color: inherit; font-weight: 750; text-decoration: underline; }
.db-alert.wait { background: #fff7e6; color: #92400e; border-color: #fcd34d; }
.db-alert.bad { background: #fef2f2; color: #991b1b; border-color: #fca5a5; }
.db-alert.info { background: var(--primary-light); color: var(--primary); }

.db-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.db-kpi, .db-card { min-width: 0; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
.db-kpi { padding: 16px 18px; }
.db-kpi-label { font-size: 12px; color: var(--muted); }
.db-kpi-value { font-size: 21px; font-weight: 800; letter-spacing: -.5px; margin: 6px 0 4px; font-variant-numeric: tabular-nums; }
.db-delta { font-size: 11px; font-weight: 650; color: var(--muted); }
.db-delta.up { color: #15803d; }
.db-delta.down { color: #b91c1c; }
.db-kpi-foot { font-size: 11px; color: var(--muted); margin-top: 4px; }

.db-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1.6fr) minmax(260px, 1fr); }
.db-grid.even { grid-template-columns: 1fr 1fr; }
.db-stack { display: grid; gap: 16px; align-content: start; min-width: 0; }
.db-card-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 15px 18px; border-bottom: 1px solid var(--border); }
.db-card-head h3 { margin: 0; font-size: 13px; font-weight: 750; }
.db-card-head span, .db-card-head a { font-size: 11px; color: var(--muted); text-decoration: none; }
.db-card-head a:hover { color: var(--primary); }
.db-card-body { padding: 16px 18px; }
.db-empty { padding: 22px 10px; text-align: center; color: var(--muted); font-size: 12px; }

.db-bars { display: flex; align-items: flex-end; gap: 6px; height: 170px; }
.db-bar { flex: 1; min-width: 0; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; }
.db-bar i { display: block; width: 100%; max-width: 34px; min-height: 2px; border-radius: 6px 6px 3px 3px; background: linear-gradient(180deg, #22c55e, #166534); }
.db-bar small { font-size: 9px; color: var(--muted); margin-top: 6px; white-space: nowrap; overflow: hidden; max-width: 100%; }
.db-bar em { font-style: normal; font-size: 9px; color: var(--muted); margin-bottom: 3px; white-space: nowrap; }

.db-donut-wrap { display: flex; gap: 18px; align-items: center; }
.db-donut { width: 128px; height: 128px; border-radius: 50%; flex: none; position: relative; }
.db-donut::after { content: ""; position: absolute; inset: 28px; border-radius: 50%; background: var(--surface); }
.db-legend { display: grid; gap: 10px; font-size: 12px; min-width: 0; flex: 1; }
.db-legend div { display: flex; justify-content: space-between; gap: 8px; }
.db-legend b { font-variant-numeric: tabular-nums; }
.db-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; }

.db-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.db-table th { text-align: left; font-size: 10px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; padding: 9px 18px; border-bottom: 1px solid var(--border); }
.db-table td { padding: 11px 18px; border-bottom: 1px solid var(--border); font-variant-numeric: tabular-nums; }
.db-table tr:last-child td { border-bottom: 0; }
.db-table .r { text-align: right; }
.db-table a { color: var(--primary); font-weight: 650; text-decoration: none; }

.db-list-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 12px; }
.db-list-row:last-child { border-bottom: 0; }
.db-list-row small { display: block; color: var(--muted); font-size: 10px; margin-top: 2px; }
.db-list-row .n { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
.db-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
.db-pill.low { background: #fff7e6; color: #b45309; }
.db-pill.out { background: #fef2f2; color: #b91c1c; }
.db-pill.ok { background: var(--primary-light); color: var(--primary); }
.db-pill.muted { background: var(--surface-soft); color: var(--muted); }

.db-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.db-mini { border: 1px solid var(--border); border-radius: 11px; padding: 12px; background: var(--surface-soft); }
.db-mini span { display: block; font-size: 10px; color: var(--muted); }
.db-mini strong { font-size: 14px; font-variant-numeric: tabular-nums; }

.db-quick { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
.db-quick a { display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid var(--border); border-radius: 11px; text-decoration: none; color: inherit; background: var(--surface); }
.db-quick a:hover { border-color: var(--primary); background: var(--surface-soft); }
.db-quick i { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: var(--primary-light); color: var(--primary); font-style: normal; flex: none; }
.db-quick strong { display: block; font-size: 12px; }
.db-quick small { display: block; font-size: 10px; color: var(--muted); }

@media (max-width: 1150px) {
    .db-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .db-grid, .db-grid.even { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
    .db-top h1 { font-size: 21px; }
    .db-kpi-value { font-size: 17px; }
    .db-quick { grid-template-columns: 1fr; }
    .db-donut-wrap { flex-direction: column; align-items: flex-start; }
    .db-table th, .db-table td { padding-left: 12px; padding-right: 12px; }
}
</style>
@endpush

@section('content')
@php
    $pct = fn ($v) => $trendMax > 0 ? max(2, round($v / $trendMax * 100)) : 2;
    $ago = fn ($t) => \Illuminate\Support\Carbon::parse($t)->diffForHumans();
    $deltaTxt = fn ($d) => is_null($d) ? 'No data for the previous period' : (($d >= 0 ? '▲ ' : '▼ ') . abs($d) . '% vs previous period');
    $deltaCls = fn ($d) => is_null($d) ? '' : ($d >= 0 ? 'up' : 'down');
    $payLabel = fn ($m) => $m === 'mpesa' ? 'M-Pesa' : ($m === 'split' ? 'Split' : 'Cash');
@endphp

<div class="db">

    {{-- Header --}}
    <section class="db-top">
        <div>
            <div class="db-eyebrow">{{ $rangeLabel }} · {{ now()->format('l, j F Y') }}</div>
            <h1>Good {{ $greeting }}, {{ $firstName }}</h1>
            <p>Business performance for {{ strtolower($rangeLabel) }}, compared with the period before it.</p>
        </div>
        <div class="db-row-actions">
            <nav class="db-tabs" aria-label="Date range">
                @foreach($ranges as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['range' => $key]) }}" class="{{ $range === $key ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
            @if($has('cashier.index'))
                <a class="db-btn primary" href="{{ route('cashier.index') }}">Open POS</a>
            @endif
        </div>
    </section>

    {{-- Alerts --}}
    @if($pendingMpesa || $outCount || $heldCount || $openCount)
        <section class="db-alerts">
            @if($pendingMpesa)
                <div class="db-alert wait">
                    <span>{{ $pendingMpesa }} M-Pesa {{ $pendingMpesa === 1 ? 'payment is' : 'payments are' }} waiting for confirmation.</span>
                </div>
            @endif
            @if($outCount)
                <div class="db-alert bad">
                    <span>{{ $outCount }} {{ $outCount === 1 ? 'product is' : 'products are' }} out of stock.</span>
                    @if($has('purchases.create'))<a href="{{ route('purchases.create') }}">Receive stock</a>@endif
                </div>
            @endif
            @if($heldCount || $openCount)
                <div class="db-alert info">
                    <span>{{ $openCount }} open and {{ $heldCount }} held {{ ($openCount + $heldCount) === 1 ? 'order' : 'orders' }} need attention.</span>
                    @if($has('cashier.index'))<a href="{{ route('cashier.index') }}">Go to POS</a>@endif
                </div>
            @endif
        </section>
    @endif

    {{-- KPIs --}}
    <section class="db-kpis">
        <article class="db-kpi">
            <div class="db-kpi-label">Sales</div>
            <div class="db-kpi-value">{{ $money($s['sales']) }}</div>
            <div class="db-delta {{ $deltaCls($dSales) }}">{{ $deltaTxt($dSales) }}</div>
        </article>
        <article class="db-kpi">
            <div class="db-kpi-label">Paid orders</div>
            <div class="db-kpi-value">{{ number_format($s['orders']) }}</div>
            <div class="db-delta {{ $deltaCls($dOrders) }}">{{ $deltaTxt($dOrders) }}</div>
        </article>
        <article class="db-kpi">
            <div class="db-kpi-label">Average sale</div>
            <div class="db-kpi-value">{{ $money($s['avg']) }}</div>
            <div class="db-delta {{ $deltaCls($dAvg) }}">{{ $deltaTxt($dAvg) }}</div>
        </article>
        <article class="db-kpi">
            <div class="db-kpi-label">Estimated gross profit</div>
            <div class="db-kpi-value">{{ $money($profit) }}</div>
            <div class="db-kpi-foot">{{ $marginPct }}% margin · discounts {{ $money($discounts) }}</div>
        </article>
    </section>

    {{-- Trend and payment mix --}}
    <section class="db-grid">
        <div class="db-card">
            <div class="db-card-head">
                <h3>Sales trend</h3>
                <span>{{ $rangeLabel }}</span>
            </div>
            <div class="db-card-body">
                <div class="db-bars">
                    @foreach($trend as $t)
                        <div class="db-bar" title="{{ $t['label'] }}: {{ $money($t['value']) }}">
                            <em>{{ $t['value'] > 0 ? number_format($t['value']) : '' }}</em>
                            <i style="height: {{ $pct($t['value']) }}%"></i>
                            <small>{{ $t['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head">
                <h3>Payment mix</h3>
                <span>{{ $money($cash + $mpesa) }}</span>
            </div>
            <div class="db-card-body">
                <div class="db-donut-wrap">
                    <div class="db-donut" style="background: conic-gradient(#16a34a 0 {{ $cashPct }}%, #86efac {{ $cashPct }}% 100%)"></div>
                    <div class="db-legend">
                        <div><span><i class="db-dot" style="background:#16a34a"></i>Cash</span><b>{{ $money($cash) }}</b></div>
                        <div><span><i class="db-dot" style="background:#86efac"></i>M-Pesa</span><b>{{ $money($mpesa) }}</b></div>
                        <div><span>Cash share</span><b>{{ $cashPct }}%</b></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Products and cashiers --}}
    <section class="db-grid even">
        <div class="db-card">
            <div class="db-card-head"><h3>Top products</h3><span>By revenue</span></div>
            @if($topProducts->isEmpty())
                <div class="db-empty">No product sales in this period.</div>
            @else
                <table class="db-table">
                    <thead><tr><th>Product</th><th class="r">Qty</th><th class="r">Revenue</th></tr></thead>
                    <tbody>
                    @foreach($topProducts as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td class="r">{{ $p->qty }}</td>
                            <td class="r">{{ $money($p->revenue) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="db-card">
            <div class="db-card-head"><h3>Cashier performance</h3><span>By sales</span></div>
            @if($cashiers->isEmpty())
                <div class="db-empty">No cashier sales in this period.</div>
            @else
                <table class="db-table">
                    <thead><tr><th>Cashier</th><th class="r">Orders</th><th class="r">Sales</th></tr></thead>
                    <tbody>
                    @foreach($cashiers as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td class="r">{{ $c->orders }}</td>
                            <td class="r">{{ $money($c->sales) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    {{-- Recent sales and stock --}}
    <section class="db-grid">
        <div class="db-card">
            <div class="db-card-head">
                <h3>Recent sales</h3>
                @if($has('sales.index'))<a href="{{ route('sales.index') }}">View all</a>@endif
            </div>
            @if($recentSales->isEmpty())
                <div class="db-empty">No paid sales in this period yet.</div>
            @else
                <table class="db-table">
                    <thead><tr><th>Order</th><th>When</th><th>Method</th><th class="r">Total</th></tr></thead>
                    <tbody>
                    @foreach($recentSales as $sale)
                        <tr>
                            <td>
                                @if($has('receipts.show'))
                                    <a href="{{ route('receipts.show', $sale->id) }}">#{{ $sale->id }}</a>
                                @else
                                    #{{ $sale->id }}
                                @endif
                            </td>
                            <td>{{ $ago($sale->paid_at) }}</td>
                            <td>{{ $payLabel($sale->payment_method) }}</td>
                            <td class="r">{{ $money($sale->total - $sale->discount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="db-stack">
            <div class="db-card">
                <div class="db-card-head">
                    <h3>Stock</h3>
                    @if($has('stock.index'))<a href="{{ route('stock.index') }}">Manage</a>@endif
                </div>
                <div class="db-card-body">
                    <div class="db-mini-grid" style="margin-bottom:12px">
                        <div class="db-mini"><span>Active products</span><strong>{{ $productCount }}</strong></div>
                        <div class="db-mini"><span>Stock value</span><strong>{{ $money($stockValue) }}</strong></div>
                        <div class="db-mini"><span>Low stock</span><strong>{{ $lowCount }}</strong></div>
                        <div class="db-mini"><span>Out of stock</span><strong>{{ $outCount }}</strong></div>
                    </div>
                    @forelse($stockAlerts as $p)
                        <div class="db-list-row">
                            <div>
                                {{ $p->name }}
                                <small>Reorder at {{ $p->reorder_level }}</small>
                            </div>
                            @if($p->stock <= 0)
                                <span class="db-pill out">Out · {{ $p->stock }}</span>
                            @else
                                <span class="db-pill low">Low · {{ $p->stock }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="db-empty">Every product is above its reorder level.</div>
                    @endforelse
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-head"><h3>Purchases and adjustments</h3><span>{{ $rangeLabel }}</span></div>
                <div class="db-card-body">
                    <div class="db-mini-grid">
                        <div class="db-mini"><span>Purchases</span><strong>{{ $money($purchases->s) }}</strong></div>
                        <div class="db-mini"><span>Received</span><strong>{{ $purchases->n }}</strong></div>
                        <div class="db-mini"><span>Stock adjustments</span><strong>{{ $adjustments }}</strong></div>
                        <div class="db-mini"><span>Active staff</span><strong>{{ $staffCount }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Held orders, activity and shortcuts --}}
    <section class="db-grid even">
        <div class="db-card">
            <div class="db-card-head"><h3>Held orders</h3><span>{{ $heldCount }} on hold</span></div>
            <div class="db-card-body">
                @forelse($heldOrders as $h)
                    <div class="db-list-row">
                        <div>
                            Order #{{ $h->id }}{{ $h->table_no ? ' · ' . $h->table_no : '' }}
                            <small>Held {{ $ago($h->created_at) }}</small>
                        </div>
                        <span class="n">{{ $money($h->total - $h->discount) }}</span>
                    </div>
                @empty
                    <div class="db-empty">No held orders.</div>
                @endforelse
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-head"><h3>Recent activity</h3><span>Audit log</span></div>
            <div class="db-card-body">
                @forelse($activity as $a)
                    <div class="db-list-row">
                        <div>
                            {{ $a->description }}
                            <small>{{ $a->user_name ?? 'System' }} · {{ $ago($a->created_at) }}</small>
                        </div>
                        <span class="db-pill muted">{{ $a->action }}</span>
                    </div>
                @empty
                    <div class="db-empty">No activity recorded yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Shortcuts --}}
    <section class="db-card">
        <div class="db-card-head"><h3>Shortcuts</h3><span>Jump to a module</span></div>
        <div class="db-card-body">
            <div class="db-quick">
                @if($has('cashier.index'))<a href="{{ route('cashier.index') }}"><i>▣</i><div><strong>Point of sale</strong><small>Take orders and payments</small></div></a>@endif
                @if($has('products.index'))<a href="{{ route('products.index') }}"><i>◇</i><div><strong>Products</strong><small>Catalogue and pricing</small></div></a>@endif
                @if($has('sales.index'))<a href="{{ route('sales.index') }}"><i>↗</i><div><strong>Sales</strong><small>Orders and receipts</small></div></a>@endif
                @if($has('transactions.index'))<a href="{{ route('transactions.index') }}"><i>▤</i><div><strong>Transactions</strong><small>Payments and status</small></div></a>@endif
                @if($has('stock.index'))<a href="{{ route('stock.index') }}"><i>▥</i><div><strong>Stock management</strong><small>Levels and alerts</small></div></a>@endif
                @if($has('adjustments.index'))<a href="{{ route('adjustments.index') }}"><i>⇄</i><div><strong>Stock adjustments</strong><small>Damage, loss, recounts</small></div></a>@endif
                @if($has('purchases.index'))<a href="{{ route('purchases.index') }}"><i>▣</i><div><strong>Purchases</strong><small>Receive supplier stock</small></div></a>@endif
                @if($has('suppliers.index'))<a href="{{ route('suppliers.index') }}"><i>♧</i><div><strong>Suppliers</strong><small>Contacts and status</small></div></a>@endif
                @if($has('reports.index'))<a href="{{ route('reports.index') }}"><i>▤</i><div><strong>Reports</strong><small>Sales and stock reports</small></div></a>@endif
                @if($has('activity.index'))<a href="{{ route('activity.index') }}"><i>◷</i><div><strong>Activity logs</strong><small>Full audit trail</small></div></a>@endif
                @if($canStaff && $has('staff.index'))<a href="{{ route('staff.index') }}"><i>♙</i><div><strong>Staff</strong><small>Team and permissions</small></div></a>@endif
            </div>
        </div>
    </section>

</div>
@endsection