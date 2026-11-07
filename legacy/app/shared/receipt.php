<?php
/* Receipt partial. Used by:
   - cashier dashboard (embedded in the receipt overlay)
   - /receipt/print page (print-only window)
   Expects from the caller: $h (escape function), $rid (receipt ID), $paper ('58' or '80'). */

if (!isset($h, $rid)) {
    exit('Open receipts from the cashier screen.');
}

$rcPdo   = db();
$rcId    = (int) $rid;
$rcPaper = ($paper ?? '80') === '58' ? '58' : '80';

$rcSt = $rcPdo->prepare(
    'SELECT o.*, u.name AS staff FROM orders o
     LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?'
);
$rcSt->execute([$rcId]);
$rcOrder = $rcSt->fetch();

if (!$rcOrder) {
    echo '<p class="rc-missing" style="padding:1rem;text-align:center;color:#991b1b">Receipt not found.</p>';
    return;
}

/* ---------- Store details ---------- */
$rcShop = [
    'name'    => setting('shop_name', 'POS'),
    'address' => setting('shop_address', ''),
    'phone'   => setting('shop_phone', ''),
    'email'   => setting('shop_email', ''),
    'web'     => setting('shop_website', ''),
    'kra'     => setting('shop_kra_pin', ''),
    'header'  => setting('receipt_header', ''),
    'footer'  => setting('receipt_footer', 'Thank you for your visit!'),
    'terms'   => setting('receipt_terms', ''),
];
$rcCur      = setting('currency', 'KES');
$rcTaxRate  = (float) setting('tax_rate', '0');
$rcTaxLabel = setting('tax_label', 'VAT');
$rcShowQr   = setting('show_qr', '1') === '1';
$rcMoney    = fn($v) => number_format((float) $v, 2);

/* ---------- Items ---------- */
$rcIs = $rcPdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$rcIs->execute([$rcId]);
$rcItems = $rcIs->fetchAll();

/* ---------- Figures ---------- */
$rcSub     = (float) $rcOrder['total'];
$rcDisc    = min((float) $rcOrder['discount'], $rcSub);
$rcDue     = max(0.0, $rcSub - $rcDisc);
$rcTaxIncl = $rcTaxRate > 0 ? $rcDue * $rcTaxRate / (100 + $rcTaxRate) : 0.0;
$rcCount   = (int) array_sum(array_column($rcItems, 'qty'));
$rcStatus  = $rcOrder['status'];
$rcPaid    = $rcStatus === 'paid';
$rcWhen    = ($rcPaid && $rcOrder['paid_at']) ? $rcOrder['paid_at'] : $rcOrder['created_at'];
$rcNo      = 'RCP-' . str_pad((string) $rcId, 6, '0', STR_PAD_LEFT);
$rcTitles  = ['paid' => 'Receipt', 'open' => 'Bill', 'held' => 'Bill', 'cancelled' => 'Cancelled'];
$rcTitle   = $rcTitles[$rcStatus] ?? 'Receipt';
$rcRef     = $rcOrder['mpesa_ref'] ?: ($rcPaid ? 'CASH' : '-');
$rcSig     = strtoupper(substr(sha1($rcNo . '|' . number_format($rcDue, 2, '.', '') . '|' . $rcWhen . '|' . $rcShop['name']), 0, 8));
$rcQr      = implode('*', [
    'RCPT', $rcNo, number_format($rcDue, 2, '.', ''), date('YmdHis', strtotime($rcWhen)), $rcRef, $rcSig,
]);
$rcTaxPct   = rtrim(rtrim(number_format($rcTaxRate, 2), '0'), '.');
$rcPayLabel = $rcOrder['payment_method'] === 'mpesa' ? 'M-Pesa' : ($rcPaid ? 'Cash' : '—');
?>
<style>
    .rc {
        --rc-ink: #0f172a; --rc-muted: #64748b; --rc-line: #e2e8f0;
        --rc-brand: #0f5132; --rc-brand-2: #198754; --rc-soft: #e8f5ee;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        color: var(--rc-ink); background: #fff; margin: 0 auto;
        width: 100%; max-width: 340px; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(15,23,42,.08);
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    .rc.p58 { max-width: 240px; font-size: 12px; }

    .rc-band {
        background: linear-gradient(135deg, var(--rc-brand), var(--rc-brand-2));
        color: #fff; padding: 1.1rem 1.1rem 1rem; text-align: center;
    }
    .rc-logo {
        width: 44px; height: 44px; margin: 0 auto .5rem; border-radius: 14px;
        background: rgba(255,255,255,.18); display: grid; place-items: center;
        font-weight: 800; font-size: 1.2rem; letter-spacing: .02em;
    }
    .rc-shop { font-size: 1.1rem; font-weight: 800; letter-spacing: .02em; }
    .rc.p58 .rc-shop { font-size: 1rem; }
    .rc-meta { font-size: .76rem; opacity: .9; line-height: 1.45; margin-top: .3rem; }
    .rc-note { font-size: .78rem; font-style: italic; margin-top: .4rem; opacity: .95; }

    .rc-title-row {
        display: flex; justify-content: space-between; align-items: center; gap: .5rem;
        padding: .75rem 1.1rem; border-bottom: 1px solid var(--rc-line);
    }
    .rc-title { font-weight: 800; font-size: .95rem; letter-spacing: .08em; text-transform: uppercase; }
    .rc-no { font-size: .78rem; color: var(--rc-muted); font-variant-numeric: tabular-nums; }
    .rc-status {
        font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
        padding: .22rem .6rem; border-radius: 999px; white-space: nowrap;
    }
    .rc-status.paid { background: var(--rc-soft); color: var(--rc-brand); }
    .rc-status.open, .rc-status.held { background: #fef3c7; color: #92400e; }
    .rc-status.cancelled { background: #fee2e2; color: #991b1b; }

    .rc-body { padding: .9rem 1.1rem 1rem; }

    .rc-info {
        display: grid; grid-template-columns: 1fr 1fr; gap: .55rem .8rem;
        font-size: .8rem; padding-bottom: .8rem; border-bottom: 1px dashed var(--rc-line);
    }
    .rc-info dt { color: var(--rc-muted); font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; }
    .rc-info dd { font-weight: 600; margin-top: .1rem; overflow-wrap: anywhere; }
    .rc-info .full { grid-column: 1 / -1; }

    .rc-table { width: 100%; border-collapse: collapse; margin: .8rem 0 0; font-size: .82rem; }
    .rc-table th {
        text-align: left; font-size: .66rem; text-transform: uppercase; letter-spacing: .07em;
        color: var(--rc-muted); font-weight: 700; padding: 0 0 .4rem; border-bottom: 1px solid var(--rc-line);
    }
    .rc-table th.r, .rc-table td.r { text-align: right; }
    .rc-table td { padding: .5rem 0; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .rc-table tr:last-child td { border-bottom: 0; }
    .rc-item-name { font-weight: 600; }
    .rc-item-sub { font-size: .72rem; color: var(--rc-muted); margin-top: .1rem; }
    .rc-empty { text-align: center; color: var(--rc-muted); padding: 1rem 0; font-size: .82rem; }

    .rc-totals { margin-top: .6rem; padding-top: .7rem; border-top: 1px dashed var(--rc-line); font-size: .84rem; }
    .rc-totals .r { display: flex; justify-content: space-between; gap: .5rem; padding: .18rem 0; color: #334155; }
    .rc-totals .r.neg { color: #b91c1c; }
    .rc-totals .r.muted { color: var(--rc-muted); font-size: .76rem; }
    .rc-grand {
        display: flex; justify-content: space-between; align-items: center; gap: .5rem;
        margin-top: .6rem; padding: .7rem .85rem; border-radius: 12px;
        background: var(--rc-soft); color: var(--rc-brand);
    }
    .rc-grand span { font-weight: 700; font-size: .85rem; text-transform: uppercase; letter-spacing: .06em; }
    .rc-grand strong { font-size: 1.25rem; font-weight: 800; font-variant-numeric: tabular-nums; }

    .rc-pay { margin-top: .8rem; padding-top: .7rem; border-top: 1px dashed var(--rc-line); font-size: .82rem; }
    .rc-pay .r { display: flex; justify-content: space-between; gap: .5rem; padding: .18rem 0; }
    .rc-pay .r span:first-child { color: var(--rc-muted); }
    .rc-pay .r.strong { font-weight: 700; }
    .rc-pay .paid-stamp {
        margin-top: .5rem; text-align: center; font-weight: 800; letter-spacing: .12em;
        color: var(--rc-brand); border: 2px solid var(--rc-brand); border-radius: 8px; padding: .3rem; font-size: .8rem;
        transform: rotate(-2deg);
    }
    .rc-alert {
        margin-top: .8rem; padding: .65rem .8rem; border-radius: 10px; font-size: .8rem; text-align: center;
        background: #fef3c7; color: #92400e;
    }
    .rc-alert.cancel { background: #fee2e2; color: #991b1b; }

    .rc-qr-wrap {
        display: flex; flex-direction: column; align-items: center; gap: .35rem;
        margin-top: 1rem; padding-top: .9rem; border-top: 1px dashed var(--rc-line);
    }
    .rc-qr { width: 118px; min-height: 60px; }
    .rc-qr svg { width: 100%; height: auto; display: block; }
    .rc.p58 .rc-qr { width: 100px; }
    .rc-verify { font-size: .7rem; color: var(--rc-muted); text-align: center; }
    .rc-verify b { color: var(--rc-ink); letter-spacing: .08em; }

    .rc-foot {
        margin-top: .9rem; padding: .85rem 1.1rem 1rem; background: #f8fafc;
        border-top: 1px solid var(--rc-line); text-align: center; font-size: .78rem; color: #334155; line-height: 1.5;
    }
    .rc-foot .thanks { font-weight: 800; font-size: .92rem; color: var(--rc-brand); margin-bottom: .2rem; }
    .rc-terms { font-size: .7rem; color: var(--rc-muted); margin-top: .4rem; }
    .rc-stamp { font-size: .66rem; color: #94a3b8; margin-top: .5rem; }

    @media print {
        @page { margin: 3mm; size: <?= $rcPaper ?>mm auto; }
        .rc { box-shadow: none; border-radius: 0; margin: 0; }
        .rc.p80 { max-width: 80mm; }
        .rc.p58 { max-width: 58mm; }
    }
</style>

<article class="rc p<?= $rcPaper ?>" id="receiptPrint">

    <header class="rc-band">
        <div class="rc-logo" aria-hidden="true"><?= $h(strtoupper(mb_substr($rcShop['name'], 0, 1))) ?></div>
        <div class="rc-shop"><?= $h($rcShop['name']) ?></div>
        <div class="rc-meta">
            <?php if ($rcShop['address']): ?><div><?= $h($rcShop['address']) ?></div><?php endif; ?>
            <?php if ($rcShop['phone']): ?><div>Tel: <?= $h($rcShop['phone']) ?></div><?php endif; ?>
            <?php if ($rcShop['email']): ?><div><?= $h($rcShop['email']) ?></div><?php endif; ?>
            <?php if ($rcShop['web']): ?><div><?= $h($rcShop['web']) ?></div><?php endif; ?>
            <?php if ($rcShop['kra']): ?><div>PIN: <?= $h($rcShop['kra']) ?></div><?php endif; ?>
        </div>
        <?php if ($rcShop['header']): ?><div class="rc-note"><?= $h($rcShop['header']) ?></div><?php endif; ?>
    </header>

    <div class="rc-title-row">
        <div>
            <div class="rc-title"><?= $h($rcTitle) ?></div>
            <div class="rc-no"><?= $h($rcNo) ?></div>
        </div>
        <span class="rc-status <?= $h($rcStatus) ?>"><?= $h($rcStatus) ?></span>
    </div>

    <div class="rc-body">

        <dl class="rc-info">
            <div><dt>Date</dt><dd><?= date('d M Y', strtotime($rcWhen)) ?></dd></div>
            <div><dt>Time</dt><dd><?= date('H:i', strtotime($rcWhen)) ?></dd></div>
            <div><dt>Order</dt><dd>#<?= (int) $rcOrder['id'] ?></dd></div>
            <div><dt>Served by</dt><dd><?= $h($rcOrder['staff'] ?? '—') ?></dd></div>
            <?php if ($rcOrder['table_no']): ?>
                <div class="full"><dt>Table / Customer</dt><dd><?= $h($rcOrder['table_no']) ?></dd></div>
            <?php endif; ?>
        </dl>

        <table class="rc-table">
            <thead>
                <tr><th>Item</th><th class="r">Qty</th><th class="r">Amount</th></tr>
            </thead>
            <tbody>
                <?php if (!$rcItems): ?>
                    <tr><td colspan="3" class="rc-empty">No items on this order.</td></tr>
                <?php endif; ?>
                <?php foreach ($rcItems as $it): ?>
                    <tr>
                        <td>
                            <div class="rc-item-name"><?= $h($it['name']) ?></div>
                            <div class="rc-item-sub"><?= $rcMoney($it['price']) ?> each</div>
                        </td>
                        <td class="r"><?= (int) $it['qty'] ?></td>
                        <td class="r"><?= $rcMoney($it['qty'] * $it['price']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="rc-totals">
            <div class="r"><span>Items</span><span><?= $rcCount ?></span></div>
            <div class="r"><span>Subtotal</span><span><?= $rcMoney($rcSub) ?></span></div>
            <?php if ($rcDisc > 0): ?>
                <div class="r neg"><span>Discount</span><span>−<?= $rcMoney($rcDisc) ?></span></div>
            <?php endif; ?>
            <?php if ($rcTaxRate > 0): ?>
                <div class="r muted"><span><?= $h($rcTaxLabel) ?> <?= $h($rcTaxPct) ?>% incl.</span><span><?= $rcMoney($rcTaxIncl) ?></span></div>
            <?php endif; ?>
        </div>

        <div class="rc-grand">
            <span>Total (<?= $h($rcCur) ?>)</span>
            <strong><?= $rcMoney($rcDue) ?></strong>
        </div>

        <?php if ($rcPaid): ?>
            <div class="rc-pay">
                <div class="r"><span>Paid by</span><span><?= $h($rcPayLabel) ?></span></div>
                <?php if ($rcOrder['payment_method'] === 'mpesa'): ?>
                    <div class="r"><span>M-Pesa code</span><span><?= $h($rcOrder['mpesa_ref']) ?></span></div>
                    <div class="r"><span>Amount paid</span><span><?= $rcMoney($rcDue) ?></span></div>
                <?php else: ?>
                    <div class="r"><span>Cash received</span><span><?= $rcMoney($rcOrder['amount_tendered']) ?></span></div>
                    <div class="r strong"><span>Change</span><span><?= $rcMoney($rcOrder['change_due']) ?></span></div>
                <?php endif; ?>
                <div class="paid-stamp">PAID IN FULL</div>
            </div>

        <?php elseif ($rcStatus === 'open' || $rcStatus === 'held'): ?>
            <div class="rc-alert">Amount due: <?= $h($rcCur) ?> <?= $rcMoney($rcDue) ?><br>Please settle at the counter.</div>

        <?php else: ?>
            <div class="rc-alert cancel">This order was cancelled. No payment taken.</div>
        <?php endif; ?>

        <?php if ($rcShowQr): ?>
            <div class="rc-qr-wrap">
                <div class="rc-qr" data-qr="<?= $h($rcQr) ?>" aria-label="Receipt verification QR code"></div>
                <div class="rc-verify">Scan to verify · Code <b><?= $h($rcSig) ?></b></div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="rc-foot">
        <div class="thanks"><?= $h($rcShop['footer']) ?></div>
        <?php if ($rcShop['terms']): ?><div class="rc-terms"><?= $h($rcShop['terms']) ?></div><?php endif; ?>
        <div class="rc-stamp">Printed <?= date('d M Y, H:i') ?> · Powered by <?= $h($rcShop['name']) ?> POS</div>
    </footer>

</article>