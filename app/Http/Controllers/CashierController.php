<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    private const PAY_LOCK = 'Please wait. An M-Pesa payment is still being confirmed.';

    /* ------------------------------------------------------------------
     | Page
     * ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $uid = auth()->id();
        $me  = auth()->user();

        $receiptId  = (int) $request->query('receipt', 0);
        $receiptOrd = $receiptId ? $this->ownOrder($receiptId) : null;
        if (! $receiptOrd) $receiptId = 0;

        $autoPrint = false;
        if ($receiptOrd && $receiptOrd->status === 'paid' && session()->has("autoprint.$receiptId")) {
            $autoPrint = (bool) session()->pull("autoprint.$receiptId");
        }

        $selectedId = (int) $request->query('order', 0) ?: $receiptId;
        $selected   = $selectedId ? $this->ownOrder($selectedId) : null;
        if (! $selected) $selectedId = 0;

        $cur     = $this->setting('currency', 'KES');
        $taxRate = (float) $this->setting('tax_rate', '0');
        $taxLbl  = $this->setting('tax_label', 'VAT');
        $taxPct  = rtrim(rtrim(number_format($taxRate, 2), '0'), '.');
        $money   = fn ($v) => $cur . ' ' . number_format((float) $v, 2);

        $items      = $selected ? $this->items($selectedId) : collect();
        $selIsOpen  = $selected && $selected->status === 'open';
        $selHeld    = $selected && $selected->status === 'held';
        $selDue     = $selected ? $this->due($selected) : 0.0;
        $sum        = $selected ? $this->summary($selected) : ['cash' => 0, 'mpesa' => 0, 'paid' => 0, 'remaining' => 0];
        $selPending = $selIsOpen && $this->hasPending($selectedId);
        $editLocked = $selIsOpen && ($selPending || $sum['paid'] > 0);
        $selTax     = ($selected && $taxRate > 0) ? $selDue * $taxRate / (100 + $taxRate) : 0.0;

        $inCart = [];
        if ($selIsOpen) {
            foreach ($items as $it) {
                $inCart[(int) $it->product_id] = (int) $it->qty;
            }
        }

        $lastPay = $selectedId
            ? DB::table('payments')->where('order_id', $selectedId)->orderByDesc('id')->first()
            : null;

        $banner = $this->banner($selIsOpen, $selPending, $sum, $lastPay);

        $paper = in_array($request->query('paper'), ['58', '80'], true)
            ? $request->query('paper')
            : ($this->setting('receipt_paper', '80') === '58' ? '58' : '80');

        /*
         * Active products for the cashier catalogue.
         * Additional fields support product images and stock badges.
         */
        $products = DB::table('products as p')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('p.active', 1)
            ->orderBy('c.name')
            ->orderBy('p.name')
            ->select(
                'p.id',
                'p.name',
                'p.price',
                'p.sku',
                'p.stock',
                'p.image_path',
                'p.track_stock',
                'p.reorder_level',
                DB::raw("COALESCE(c.name, 'General') as category")
            )
            ->get();

        $categories = $products->pluck('category')->unique()->values();

        $quick = [round($sum['remaining'], 2)];
        foreach ([500, 1000, 2000, 5000] as $note) {
            if ($note > $sum['remaining']) $quick[] = $note;
        }
        $quick = array_slice(array_values(array_unique($quick)), 0, 4);

        return view('cashier.index', [
            'me'          => $me,
            'meInit'      => strtoupper(mb_substr($me->name, 0, 1)),
            'shop'        => $this->setting('shop_name', 'POS'),
            'shopAddr'    => $this->setting('shop_address', ''),
            'shopTel'     => $this->setting('shop_phone', ''),
            'shopPin'     => $this->setting('shop_pin', ''),
            'footer'      => $this->setting('receipt_footer', 'Thank you for your business!'),
            'cur'         => $cur,
            'taxRate'     => $taxRate,
            'taxLabel'    => $taxLbl,
            'taxPct'      => $taxPct,
            'm'           => $money,
            'today'       => $this->today($uid, $money),
            'products'    => $products,
            'categories'  => $categories,
            'openOrders'  => $this->listOrders('open'),
            'heldOrders'  => $this->listOrders('held'),
            'recentPaid'  => $this->recentPaid($uid),
            'selected'    => $selected,
            'selectedId'  => $selectedId,
            'items'       => $items,
            'selIsOpen'   => $selIsOpen,
            'selHeld'     => $selHeld,
            'selDue'      => $selDue,
            'sum'         => $sum,
            'selPending'  => $selPending,
            'editLocked'  => $editLocked,
            'selTax'      => $selTax,
            'inCart'      => $inCart,
            'banner'      => $banner,
            'quick'       => $quick,
            'paper'       => $paper,
            'receipt'     => $receiptOrd ? $this->receipt($receiptOrd, $receiptId, $taxRate, $cur) : null,
            'receiptOrd'  => $receiptOrd,
            'receiptId'   => $receiptId,
            'autoPrint'   => $autoPrint || (bool) $request->query('print'),
            'closeUrl'    => $selectedId ? route('cashier.index', ['order' => $selectedId]) : route('cashier.index'),
        ]);
    }

    /* ------------------------------------------------------------------
     | Actions (one POST endpoint, switched on `action`)
     * ------------------------------------------------------------------ */
    public function act(Request $request)
    {
        $uid    = auth()->id();
        $oid    = (int) $request->input('order_id', 0);
        $action = (string) $request->input('action', '');
        $target = route('cashier.index', $oid ? ['order' => $oid] : []);

        try {
            switch ($action) {
                case 'new':
                    $oid = DB::table('orders')->insertGetId([
                        'user_id' => $uid, 'status' => 'open', 'table_no' => $this->tableName($request),
                        'total' => 0, 'discount' => 0, 'created_at' => now(),
                    ]);
                    return $this->go(route('cashier.index', ['order' => $oid]), 'success', "Order #$oid opened.");

                case 'add':
                    $product = DB::table('products')
                        ->where('id', (int) $request->input('product_id'))
                        ->where('active', 1)
                        ->first();

                    if (! $product) return $this->go($target, 'error', 'That product is not available.');

                    $order = $oid ? $this->ownOrder($oid) : null;
                    if ($order && $block = $this->editBlock($order)) {
                        return $this->go($target, 'error', $block);
                    }

                    if (! $order || $order->status !== 'open') {
                        $oid = DB::table('orders')->insertGetId([
                            'user_id' => $uid, 'status' => 'open', 'total' => 0, 'discount' => 0, 'created_at' => now(),
                        ]);
                    }

                    $existing = DB::table('order_items')
                        ->where('order_id', $oid)
                        ->where('product_id', $product->id)
                        ->first();

                    if ($existing) {
                        DB::table('order_items')->where('id', $existing->id)
                            ->update(['qty' => min(99, $existing->qty + 1)]);
                    } else {
                        DB::table('order_items')->insert([
                            'order_id' => $oid, 'product_id' => $product->id,
                            'name' => $product->name, 'price' => $product->price, 'qty' => 1,
                        ]);
                    }

                    $this->recalc($oid);
                    return redirect(route('cashier.index', ['order' => $oid]));

                case 'qty':
                    $order = $this->ownOrder($oid);
                    if (! $order || $order->status !== 'open') return $this->go($target, 'error', 'This order is closed.');
                    if ($block = $this->editBlock($order)) return $this->go($target, 'error', $block);

                    $item = (int) $request->input('item_id', 0);
                    $qty  = (int) $request->input('qty', 0);

                    if ($qty <= 0) {
                        DB::table('order_items')->where('id', $item)->where('order_id', $oid)->delete();
                    } else {
                        DB::table('order_items')->where('id', $item)->where('order_id', $oid)->update(['qty' => min(99, $qty)]);
                    }

                    $this->recalc($oid);
                    return redirect($target);

                case 'rename':
                    $order = $this->ownOrder($oid);
                    if (! $order || $order->status !== 'open') return $this->go($target, 'error', 'This order is closed.');
                    if ($block = $this->editBlock($order)) return $this->go($target, 'error', $block);

                    DB::table('orders')->where('id', $oid)->update(['table_no' => $this->tableName($request)]);
                    return $this->go($target, 'success', 'Table or customer updated.');

                case 'discount':
                    $order = $this->ownOrder($oid);
                    if (! $order || $order->status !== 'open') return $this->go($target, 'error', 'This order is closed.');
                    if ($block = $this->editBlock($order)) return $this->go($target, 'error', $block);

                    $amt = round(min(max(0.0, (float) $request->input('discount', 0)), (float) $order->total), 2);
                    DB::table('orders')->where('id', $oid)->update(['discount' => $amt]);

                    return $this->go($target, 'success', $amt > 0
                        ? 'Discount of ' . $this->money($amt) . ' applied.' : 'Discount removed.');

                case 'hold':
                    $order = $this->ownOrder($oid);
                    if (! $order || $order->status !== 'open') return $this->go($target, 'error', 'This order cannot be held.');
                    if ($block = $this->editBlock($order)) return $this->go($target, 'error', $block);

                    DB::table('orders')->where('id', $oid)->update(['status' => 'held']);
                    return $this->go(route('cashier.index'), 'success', "Order #$oid put on hold.");

                case 'resume':
                    $n = DB::table('orders')->where('id', $oid)->where('user_id', $uid)->where('status', 'held')
                        ->update(['status' => 'open']);

                    if ($n === 0) return $this->go($target, 'error', 'This order cannot be resumed.');
                    return $this->go($target, 'success', "Order #$oid resumed.");

                case 'cancel':
                    if ($this->hasPending($oid)) return $this->go($target, 'error', self::PAY_LOCK);

                    if ($this->hasSuccess($oid)) {
                        return $this->go($target, 'error', 'This order has payments and cannot be cancelled here. Ask a manager.');
                    }

                    $n = DB::table('orders')->where('id', $oid)->where('user_id', $uid)
                        ->whereIn('status', ['open', 'held'])->update(['status' => 'cancelled']);

                    if ($n === 0) return $this->go($target, 'error', 'This order is already closed.');
                    return $this->go(route('cashier.index'), 'success', "Order #$oid cancelled.");

                case 'pay':
                    $order = $this->ownOrder($oid);
                    if (! $order || $order->status !== 'open') return $this->go($target, 'error', 'This order is already closed.');

                    $method = (string) $request->input('method', '');

                    if ($method === 'cash') {
                        $r = $this->payCash($oid, $request);
                    } elseif ($method === 'mpesa') {
                        $r = app(PaymentService::class)->startStk(
                            $oid, $uid,
                            (string) $request->input('phone_number', ''),
                            (int) $request->input('mpesa_amount', 0),
                            auth()->user()->name
                        );
                    } else {
                        return $this->go($target, 'error', 'Choose cash or M-Pesa.');
                    }

                    session()->put("autoprint.$oid", $request->boolean('print'));

                    $to = route('cashier.index', ['order' => $oid] + (! empty($r['settled']) ? ['receipt' => $oid] : []));
                    return $this->go($to, $r['ok'] ? 'success' : 'error', $r['message']);
            }
        } catch (\Throwable $e) {
            report($e);
            return $this->go($target, 'error', 'Could not save. Please try again.');
        }

        return redirect($target);
    }

    /* ------------------------------------------------------------------
     | Polled by the page while an M-Pesa prompt is open
     * ------------------------------------------------------------------ */
    public function paymentStatus(Request $request)
    {
        $oid   = (int) $request->query('order', 0);
        $order = $this->ownOrder($oid);

        if (! $order) return response()->json(['done' => true, 'order_status' => null]);

        return response()->json([
            'done'         => ! $this->hasPending($oid),
            'order_status' => $order->status,
        ]);
    }

    /* ------------------------------------------------------------------
     | Cash payment (row lock, so two tills cannot overpay)
     * ------------------------------------------------------------------ */
    private function payCash(int $oid, Request $req): array
    {
        $applied  = round((float) str_replace(',', '', (string) $req->input('cash_applied', 0)), 2);
        $tendered = round((float) str_replace(',', '', (string) $req->input('cash_tendered', 0)), 2);

        return DB::transaction(function () use ($oid, $applied, $tendered) {
            $o = DB::table('orders')->where('id', $oid)->lockForUpdate()->first();

            if (! $o || $o->status !== 'open') return $this->result(false, 'This order is already closed.');
            if ($this->hasPending($oid)) return $this->result(false, self::PAY_LOCK);

            $s         = $this->summary($o);
            $remaining = $s['remaining'];

            if ($applied <= 0) return $this->result(false, 'Enter the cash amount.');
            if ($applied > $remaining + 0.005) return $this->result(false, 'Cash is more than the balance due.');
            if ($tendered + 0.005 < $applied) return $this->result(false, 'Cash received is less than the amount applied.');

            $covers = $applied >= $remaining - 0.005;
            if ($covers) {
    $stockError = $this->deductOrderStock($oid, (int) $o->user_id);

    if ($stockError) {
        return $this->result(false, $stockError);
    }
}
            if (! $covers && $tendered > $applied + 0.005) {
                return $this->result(false, 'Change is only given when cash covers the full balance.');
            }

            $change = $covers ? round($tendered - $applied, 2) : 0.0;

            DB::table('payments')->insert([
                'order_id' => $oid, 'payment_method' => 'cash', 'amount' => $applied,
                'status' => 'success', 'created_at' => now(),
            ]);

            if (! $covers) {
                return [
                    'ok'      => true,
                    'settled' => false,
                    'message' => 'Cash of ' . $this->money($applied) . ' recorded. Balance due '
                                 . $this->money($remaining - $applied) . '.',
                ];
            }

            DB::table('orders')->where('id', $oid)->update([
                'status'         => 'paid',
                'paid_at'        => now(),
                'change_due'     => $change,
                'payment_method' => $s['mpesa'] > 0 ? 'split' : 'cash',
            ]);

            return [
                'ok'      => true,
                'settled' => true,
                'message' => "Order #$oid paid. Change " . $this->money($change) . '.',
            ];
        });
    }
private function deductOrderStock(int $oid, int $userId): ?string
{
    $items = DB::table('order_items')
        ->where('order_id', $oid)
        ->select('product_id')
        ->selectRaw('SUM(qty) as quantity')
        ->groupBy('product_id')
        ->get();

    $updates = [];

    // First validate all products before changing any stock.
    foreach ($items as $item) {
        $product = DB::table('products')
            ->where('id', $item->product_id)
            ->lockForUpdate()
            ->first();

        if (!$product) {
            return "A product in order #{$oid} no longer exists.";
        }

        if (isset($product->track_stock) && !$product->track_stock) {
            continue;
        }

        $quantity = (int) $item->quantity;
        $before = (int) $product->stock;

        if ($quantity <= 0 || $before < $quantity) {
            return "Insufficient stock for {$product->name}. Available: {$before}. Required: {$quantity}.";
        }

        $updates[] = [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'before' => $before,
            'after' => $before - $quantity,
        ];
    }

    // Deduct stock only after every product passes validation.
    foreach ($updates as $item) {
        DB::table('products')
            ->where('id', $item['product_id'])
            ->update([
                'stock' => $item['after'],
                'updated_at' => now(),
            ]);

        DB::table('stock_adjustments')->insert([
            'product_id' => $item['product_id'],
            'user_id' => $userId,
            'change' => -$item['quantity'],
            'stock_before' => $item['before'],
            'stock_after' => $item['after'],
            'reason' => 'sale',
            'note' => "Stock deducted for order #{$oid}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return null;
}
    /* ------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------ */
    private function ownOrder(int $id): ?object
    {
        if (! $id) return null;

        return DB::table('orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.id', $id)
            ->where('o.user_id', auth()->id())
            ->select('o.*', 'u.name as staff')
            ->first();
    }

    private function items(int $oid)
    {
        return DB::table('order_items')->where('order_id', $oid)->orderBy('id')->get();
    }

    private function due(object $o): float
    {
        return max(0.0, (float) $o->total - (float) $o->discount);
    }

    private function summary(object $o): array
    {
        $sums = DB::table('payments')->where('order_id', $o->id)->where('status', 'success')
            ->selectRaw('payment_method, SUM(amount) AS s')->groupBy('payment_method')
            ->pluck('s', 'payment_method');

        $cash  = (float) ($sums['cash'] ?? 0);
        $mpesa = (float) ($sums['mpesa'] ?? 0);

        return [
            'cash'      => $cash,
            'mpesa'     => $mpesa,
            'paid'      => $cash + $mpesa,
            'remaining' => max(0.0, $this->due($o) - $cash - $mpesa),
        ];
    }

    private function hasPending(int $oid): bool
    {
        return DB::table('payments')->where('order_id', $oid)->where('payment_method', 'mpesa')
            ->whereIn('status', ['pending', 'unknown'])->exists();
    }

    private function hasSuccess(int $oid): bool
    {
        return DB::table('payments')->where('order_id', $oid)->where('status', 'success')->exists();
    }

    private function editBlock(object $o): ?string
    {
        if ($o->status !== 'open') return null;
        if ($this->hasPending($o->id)) return self::PAY_LOCK;
        if ($this->hasSuccess($o->id)) return 'Payment has started on this order, so it can no longer change.';

        return null;
    }

    private function recalc(int $oid): void
    {
        $total = (float) DB::table('order_items')->where('order_id', $oid)
            ->selectRaw('COALESCE(SUM(qty * price), 0) AS t')->value('t');

        $discount = min((float) DB::table('orders')->where('id', $oid)->value('discount'), $total);

        DB::table('orders')->where('id', $oid)->update(['total' => $total, 'discount' => $discount]);
    }

    private function tableName(Request $r): ?string
    {
        $t = mb_substr(trim((string) $r->input('table_no', '')), 0, 20);

        return $t !== '' ? $t : null;
    }

    private function listOrders(string $status)
    {
        return DB::table('orders as o')
            ->where('o.user_id', auth()->id())->where('o.status', $status)
            ->select('o.id', 'o.table_no', 'o.total', 'o.discount', 'o.created_at')
            ->selectRaw('(SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count')
            ->orderBy('o.created_at')->limit(60)->get();
    }

    private function recentPaid(int $uid)
    {
        return DB::table('orders')->where('user_id', $uid)->where('status', 'paid')
            ->orderByDesc('paid_at')->limit(10)->get();
    }

    private function today(int $uid, \Closure $money): array
    {
        $start = now()->startOfDay();

        $t = DB::table('orders')->where('user_id', $uid)->where('status', 'paid')->where('paid_at', '>=', $start)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total - discount), 0) AS sales')->first();

        $pm = DB::table('payments as p')->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('o.user_id', $uid)->where('o.status', 'paid')->where('o.paid_at', '>=', $start)
            ->where('p.status', 'success')
            ->selectRaw('p.payment_method AS m, SUM(p.amount) AS s')->groupBy('p.payment_method')
            ->pluck('s', 'm');

        // Orders paid before payment rows existed: fall back to the order's own method.
        $legacy = DB::table('orders as o')->where('o.user_id', $uid)->where('o.status', 'paid')
            ->where('o.paid_at', '>=', $start)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('payments as p')
                ->whereColumn('p.order_id', 'o.id')->where('p.status', 'success'))
            ->selectRaw('o.payment_method AS m, SUM(o.total - o.discount) AS s')->groupBy('o.payment_method')
            ->pluck('s', 'm');

        $cash  = (float) ($pm['cash'] ?? 0) + (float) ($legacy['cash'] ?? 0);
        $mpesa = (float) ($pm['mpesa'] ?? 0) + (float) ($legacy['mpesa'] ?? 0);

        return [
            'n'         => (int) $t->n,
            'sales'     => (float) $t->sales,
            'sales_fmt' => $money($t->sales),
            'cash'      => $money($cash),
            'mpesa'     => $money($mpesa),
        ];
    }

    private function banner(bool $open, bool $pending, array $sum, ?object $last): ?array
    {
        if ($open && $pending) {
            return ($last->status ?? '') === 'unknown'
                ? ['wait', 'Checking the M-Pesa payment', 'Please wait. Items and payment stay locked until it is confirmed.']
                : ['wait', 'Waiting for M-Pesa PIN', 'Ask the customer to enter their PIN. This page updates automatically.'];
        }

        if ($open && $last && $last->status === 'failed') {
            return ['bad', 'M-Pesa payment not completed', $last->response_message ?: 'Try again or take cash.'];
        }

        if ($open && $last && $last->status === 'review') {
            return ['bad', 'Payment needs a manager check', $last->response_message ?: 'Ask a manager to check this payment.'];
        }

        if ($open && $sum['paid'] > 0 && $sum['remaining'] > 0) {
            return ['info', 'Part paid', 'Balance due ' . $this->money($sum['remaining'])
                . '. Take the rest in cash or M-Pesa. Items can no longer change.'];
        }

        return null;
    }

    private function receipt(object $o, int $id, float $taxRate, string $cur): array
    {
        $due = $this->due($o);
        $tax = $taxRate > 0 ? $due * $taxRate / (100 + $taxRate) : 0.0;
        $no  = 'RCP-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);

        $pays = DB::table('payments')->where('order_id', $id)->where('status', 'success')->orderBy('id')->get();
        $paid = round((float) $pays->sum('amount'), 2);

        $mpesaRef = $pays->where('payment_method', 'mpesa')->whereNotNull('mpesa_receipt')->last();
        $ref      = $mpesaRef ? (string) $mpesaRef->mpesa_receipt : '';

        return [
            'no'      => $no,
            'due'     => $due,
            'tax'     => $tax,
            'items'   => $this->items($id),
            'pays'    => $pays,
            'isPaid'  => $o->status === 'paid',
            'balance' => max(0.0, round($due - $paid, 2)),
            'qr'      => $this->setting('shop_name', 'POS') . ' | ' . $no . ' | ' . $cur . ' '
                         . number_format($due, 2, '.', '') . ($ref !== '' ? ' | ' . $ref : ''),
        ];
    }

    private function result(bool $ok, string $message): array
    {
        return ['ok' => $ok, 'settled' => false, 'message' => $message];
    }

    private function go(string $to, string $type = 'success', ?string $msg = null)
    {
        $r = redirect($to);

        return $msg ? $r->with('flash', ['type' => $type, 'msg' => $msg]) : $r;
    }

    private function money(float|string $v): string
    {
        return $this->setting('currency', 'KES') . ' ' . number_format((float) $v, 2);
    }

    private function setting(string $key, string $default = ''): string
    {
        return (string) (DB::table('settings')->where('key', $key)->value('value') ?? $default);
    }
}
