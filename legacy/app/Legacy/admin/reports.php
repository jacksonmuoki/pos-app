<?php
/* Admin reports: sales, cashiers, products, payments, receipts and CSV export on one page.
   Print mode renders the same receipt design as the till, plus a long receipt strip. */

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money($v): string
    {
        return setting('currency', 'KES') . ' ' . number_format((float) $v, 2);
    }
}

if (!function_exists('rep_num')) {
    function rep_num($v): string
    {
        return number_format((float) $v, 2);
    }
}

if (!function_exists('rep_rows')) {
    /** Runs a query and returns all rows. Errors are logged and flagged, never thrown to the page. */
    function rep_rows(PDO $pdo, string $sql, array $args = []): array
    {
        try {
            $s = $pdo->prepare($sql);
            $s->execute($args);
            return $s->fetchAll();
        } catch (PDOException $e) {
            error_log('Reports query failed: ' . $e->getMessage());
            $GLOBALS['rep_db_error'] = true;
            return [];
        }
    }
}

if (!function_exists('rep_csv')) {
    function rep_csv(string $name, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads the file correctly
        fputcsv($out, $header);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }
}

if (!function_exists('rep_load_orders')) {
    /** Loads orders with their items and payments. Returns [orderId => pack]. */
    function rep_load_orders(PDO $pdo, array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $out = [];

        foreach (rep_rows($pdo,
            "SELECT o.*, u.name AS staff FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE o.id IN ($ph)", $ids) as $o) {
            $out[(int) $o['id']] = ['o' => $o, 'items' => [], 'pays' => []];
        }
        foreach (rep_rows($pdo, "SELECT * FROM order_items WHERE order_id IN ($ph) ORDER BY id", $ids) as $i) {
            if (isset($out[(int) $i['order_id']])) {
                $out[(int) $i['order_id']]['items'][] = $i;
            }
        }
        foreach (rep_rows($pdo,
            "SELECT order_id, payment_method, amount, mpesa_receipt FROM payments
             WHERE status = 'success' AND order_id IN ($ph) ORDER BY id", $ids) as $p) {
            if (isset($out[(int) $p['order_id']])) {
                $out[(int) $p['order_id']]['pays'][] = $p;
            }
        }
        return $out;
    }
}

if (!function_exists('rep_collect')) {
    /** Every figure the page and the printed summaries use. */
    function rep_collect(PDO $pdo, string $paidSql, array $paidArgs, string $start, string $end, int $cashierId): array
    {
        $d = [];

        $k = rep_rows($pdo,
            "SELECT COUNT(*) AS n, COALESCE(SUM(o.total),0) AS gross,
                    COALESCE(SUM(o.discount),0) AS disc,
                    COALESCE(SUM(o.total - o.discount),0) AS net,
                    COALESCE(SUM(o.change_due),0) AS chg
             FROM orders o WHERE $paidSql", $paidArgs)[0] ?? [];
        $d['n']     = (int) ($k['n'] ?? 0);
        $d['gross'] = (float) ($k['gross'] ?? 0);
        $d['disc']  = (float) ($k['disc'] ?? 0);
        $d['net']   = (float) ($k['net'] ?? 0);
        $d['chg']   = (float) ($k['chg'] ?? 0);
        $d['avg']   = $d['n'] ? $d['net'] / $d['n'] : 0.0;

        $cw    = $cashierId ? ' AND o.user_id = ?' : '';
        $cArgs = array_merge([$start, $end], $cashierId ? [$cashierId] : []);
        $c = rep_rows($pdo,
            "SELECT COUNT(*) AS n, COALESCE(SUM(o.total),0) AS s FROM orders o
             WHERE o.status = 'cancelled' AND o.created_at >= ? AND o.created_at < ?$cw", $cArgs)[0] ?? [];
        $d['cancelled_n'] = (int) ($c['n'] ?? 0);
        $d['cancelled_s'] = (float) ($c['s'] ?? 0);

        $d['mix'] = ['cash' => ['n' => 0, 's' => 0.0], 'mpesa' => ['n' => 0, 's' => 0.0], 'split' => ['n' => 0, 's' => 0.0]];
        foreach (rep_rows($pdo,
            "SELECT o.payment_method AS m, COUNT(*) AS n, COALESCE(SUM(o.total - o.discount),0) AS s
             FROM orders o WHERE $paidSql GROUP BY o.payment_method", $paidArgs) as $r) {
            if (isset($d['mix'][$r['m']])) {
                $d['mix'][$r['m']] = ['n' => (int) $r['n'], 's' => (float) $r['s']];
            }
        }

        $d['collected'] = ['cash' => 0.0, 'mpesa' => 0.0];
        foreach (rep_rows($pdo,
            "SELECT p.payment_method AS m, COALESCE(SUM(p.amount),0) AS s
             FROM payments p JOIN orders o ON o.id = p.order_id
             WHERE p.status = 'success' AND $paidSql GROUP BY p.payment_method", $paidArgs) as $r) {
            if (isset($d['collected'][$r['m']])) {
                $d['collected'][$r['m']] = (float) $r['s'];
            }
        }

        $d['daily'] = rep_rows($pdo,
            "SELECT DATE(o.paid_at) AS d, COUNT(*) AS n,
                    COALESCE(SUM(o.total - o.discount),0) AS net,
                    COALESCE(SUM(CASE WHEN o.payment_method='cash'  THEN o.total - o.discount ELSE 0 END),0) AS cash,
                    COALESCE(SUM(CASE WHEN o.payment_method='mpesa' THEN o.total - o.discount ELSE 0 END),0) AS mpesa,
                    COALESCE(SUM(CASE WHEN o.payment_method='split' THEN o.total - o.discount ELSE 0 END),0) AS split
             FROM orders o WHERE $paidSql GROUP BY d ORDER BY d DESC", $paidArgs);

        $d['staff'] = rep_rows($pdo,
            "SELECT u.id, u.name, COUNT(o.id) AS n,
                    COALESCE(SUM(o.total - o.discount),0) AS net,
                    COALESCE(SUM(o.discount),0) AS disc,
                    COALESCE(AVG(o.total - o.discount),0) AS avg,
                    SUM(o.payment_method = 'split') AS split_n
             FROM orders o JOIN users u ON u.id = o.user_id
             WHERE $paidSql GROUP BY u.id, u.name ORDER BY net DESC", $paidArgs);

        $d['hourly'] = array_fill(0, 24, ['n' => 0, 's' => 0.0]);
        foreach (rep_rows($pdo,
            "SELECT HOUR(o.paid_at) AS hr, COUNT(*) AS n, COALESCE(SUM(o.total - o.discount),0) AS s
             FROM orders o WHERE $paidSql GROUP BY hr", $paidArgs) as $r) {
            $d['hourly'][(int) $r['hr']] = ['n' => (int) $r['n'], 's' => (float) $r['s']];
        }

        $d['products'] = rep_rows($pdo,
            "SELECT oi.product_id AS pid, oi.name, COALESCE(p.category,'Uncategorised') AS cat,
                    SUM(oi.qty) AS qty,
                    COALESCE(SUM(oi.qty * oi.price),0) AS rev,
                    COALESCE(SUM(oi.qty * COALESCE(p.cost_price,0)),0) AS cost
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE $paidSql
             GROUP BY oi.product_id, oi.name, p.category
             ORDER BY rev DESC LIMIT 50", $paidArgs);

        $d['categories'] = rep_rows($pdo,
            "SELECT COALESCE(p.category,'Uncategorised') AS cat, SUM(oi.qty) AS qty,
                    COALESCE(SUM(oi.qty * oi.price),0) AS rev
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE $paidSql GROUP BY p.category ORDER BY rev DESC", $paidArgs);

        $cost = rep_rows($pdo,
            "SELECT COALESCE(SUM(oi.qty * COALESCE(p.cost_price,0)),0) AS cost
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE $paidSql", $paidArgs)[0] ?? [];
        $d['cost']   = (float) ($cost['cost'] ?? 0);
        $d['profit'] = $d['net'] - $d['cost'];

        $pw    = $cashierId ? ' AND p.user_id = ?' : '';
        $pArgs = array_merge([$start, $end], $cashierId ? [$cashierId] : []);

        $d['pstats'] = ['success' => 0, 'failed' => 0, 'review' => 0, 'waiting' => 0];
        foreach (rep_rows($pdo,
            "SELECT p.status AS s, COUNT(*) AS n FROM payments p
             WHERE p.payment_method = 'mpesa' AND p.created_at >= ? AND p.created_at < ?$pw
             GROUP BY p.status", $pArgs) as $r) {
            $key = in_array($r['s'], ['pending', 'unknown'], true) ? 'waiting' : $r['s'];
            if (isset($d['pstats'][$key])) {
                $d['pstats'][$key] += (int) $r['n'];
            }
        }

        $d['payments'] = rep_rows($pdo,
            "SELECT p.created_at, p.order_id, p.amount, p.status, p.mpesa_receipt,
                    p.response_message, u.name AS staff
             FROM payments p LEFT JOIN users u ON u.id = p.user_id
             WHERE p.payment_method = 'mpesa' AND p.created_at >= ? AND p.created_at < ?$pw
             ORDER BY p.id DESC LIMIT 100", $pArgs);

        $d['orders'] = rep_rows($pdo,
            "SELECT o.id, o.paid_at, o.payment_method, o.table_no, o.total, o.discount, u.name AS staff
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE $paidSql ORDER BY o.paid_at DESC LIMIT 300", $paidArgs);

        return $d;
    }
}

if (!function_exists('rep_rc_css')) {
    function rep_rc_css(): string
    {
        return <<<'CSS'
.rc{background:#fff;color:#000;font-family:"Courier New",ui-monospace,Menlo,Consolas,monospace;font-size:12px;line-height:1.35;width:80mm;max-width:100%;padding:5mm 4mm 4mm;font-weight:600}
.rc.p58{width:58mm;font-size:10.5px;padding:4mm 3mm 3mm}
.rc *{box-sizing:border-box}
.rc .c{text-align:center}
.rc .shop{font-size:1.5em;font-weight:800;text-transform:uppercase;letter-spacing:.05em;line-height:1.15;margin-bottom:3px}
.rc .sub{font-weight:600;font-size:.95em}
.rc .sep{border:0;border-top:1px dashed #000;margin:6px 0}
.rc .row{display:flex;justify-content:space-between;gap:8px;align-items:baseline}
.rc .row>span:last-child{text-align:right;white-space:nowrap}
.rc .title{text-align:center;font-weight:800;letter-spacing:.2em;text-transform:uppercase;margin:2px 0}
.rc .cols{display:flex;justify-content:space-between;font-weight:800;border-bottom:1px solid #000;padding-bottom:2px;margin-bottom:3px}
.rc .it{margin:0 0 4px}
.rc .it .nm{font-weight:700;word-break:break-word}
.rc .it .calc{display:flex;justify-content:space-between;padding-left:2ch}
.rc .cnt{font-weight:700}
.rc .grand{display:flex;justify-content:space-between;align-items:baseline;font-size:1.55em;font-weight:800;border-top:2px solid #000;border-bottom:2px solid #000;padding:3px 0;margin:6px 0}
.rc .stamp{border:2px solid #000;text-align:center;padding:3px 0;margin:6px 0;font-weight:800;letter-spacing:.12em}
.rc .qr{display:flex;justify-content:center;margin:8px 0 2px}
.rc .qr svg{width:26mm;height:26mm}
.rc.p58 .qr svg{width:22mm;height:22mm}
.rc .bc{margin:8px 0 2px}
.rc .bc svg{display:block;width:100%;height:12mm}
.rc .bc small{display:block;text-align:center;letter-spacing:.15em;margin-top:2px;font-size:.9em}
.rc .foot{text-align:center;font-weight:700;margin-top:4px}
.rc .fine{text-align:center;font-weight:600;font-size:.88em;margin-top:2px}
.rc .sig{margin-top:18px;border-top:1px solid #000;padding-top:3px;text-align:center;font-size:.9em}
CSS;
    }
}

if (!function_exists('rep_cfg')) {
    function rep_cfg(): array
    {
        $cfg = [
            'shop'     => (string) setting('shop_name', 'POS'),
            'addr'     => (string) setting('shop_address', ''),
            'tel'      => (string) setting('shop_phone', ''),
            'pin'      => (string) setting('shop_pin', ''),
            'footer'   => (string) setting('receipt_footer', 'Thank you for your business!'),
            'cur'      => (string) setting('currency', 'KES'),
            'taxRate'  => (float) setting('tax_rate', '0'),
            'taxLabel' => (string) setting('tax_label', 'VAT'),
        ];
        $cfg['taxPct'] = rtrim(rtrim(number_format($cfg['taxRate'], 2), '0'), '.');
        $paperIn = (string) ($_GET['paper'] ?? '');
        $cfg['paper'] = in_array($paperIn, ['58', '80'], true)
            ? $paperIn
            : (setting('receipt_paper', '80') === '58' ? '58' : '80');
        return $cfg;
    }
}

if (!function_exists('rep_receipt_html')) {
    /** One receipt, laid out exactly like the till receipt. */
    function rep_receipt_html(array $pack, array $cfg): string
    {
        $o     = $pack['o'];
        $items = $pack['items'];
        $pays  = $pack['pays'];

        $due  = max(0.0, (float) $o['total'] - (float) $o['discount']);
        $tax  = $cfg['taxRate'] > 0 ? $due * $cfg['taxRate'] / (100 + $cfg['taxRate']) : 0.0;
        $no   = 'RCP-' . str_pad((string) $o['id'], 6, '0', STR_PAD_LEFT);
        $when = $o['paid_at'] ?: $o['created_at'];
        $ord  = '#' . (int) $o['id'] . ($o['table_no'] ? ' / ' . $o['table_no'] : '');

        $mref = '';
        $qty  = 0;
        foreach ($pays as $p) {
            if ($p['payment_method'] === 'mpesa' && !empty($p['mpesa_receipt'])) {
                $mref = (string) $p['mpesa_receipt'];
            }
        }
        foreach ($items as $it) {
            $qty += (int) $it['qty'];
        }
        $qr = $cfg['shop'] . ' | ' . $no . ' | ' . $cfg['cur'] . ' ' . number_format($due, 2, '.', '')
            . ($mref !== '' ? ' | ' . $mref : '');

        ob_start();
        ?>
        <article class="rc p<?= h($cfg['paper']) ?>">
          <div class="c">
            <div class="shop"><?= h($cfg['shop']) ?></div>
            <?php if ($cfg['addr'] !== ''): ?><div class="sub"><?= nl2br(h($cfg['addr'])) ?></div><?php endif; ?>
            <?php if ($cfg['tel'] !== ''): ?><div class="sub">Tel: <?= h($cfg['tel']) ?></div><?php endif; ?>
            <?php if ($cfg['pin'] !== ''): ?><div class="sub">PIN: <?= h($cfg['pin']) ?></div><?php endif; ?>
          </div>
          <hr class="sep">
          <div class="title">Sales Receipt</div>
          <hr class="sep">
          <div class="row"><span>Receipt No</span><span><?= h($no) ?></span></div>
          <div class="row"><span>Order</span><span><?= h($ord) ?></span></div>
          <div class="row"><span>Date</span><span><?= h(date('d/m/Y H:i', strtotime($when))) ?></span></div>
          <div class="row"><span>Cashier</span><span><?= h($o['staff'] ?? '—') ?></span></div>
          <hr class="sep">
          <div class="cols"><span>ITEM</span><span>AMOUNT</span></div>
          <?php foreach ($items as $it): ?>
            <div class="it">
              <div class="nm"><?= h($it['name']) ?></div>
              <div class="calc">
                <span><?= (int) $it['qty'] ?> x <?= h(rep_num($it['price'])) ?></span>
                <span><?= h(rep_num($it['price'] * $it['qty'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          <hr class="sep">
          <div class="row cnt"><span>Items: <?= count($items) ?></span><span>Qty: <?= $qty ?></span></div>
          <div class="row"><span>Subtotal</span><span><?= h(rep_num($o['total'])) ?></span></div>
          <?php if ((float) $o['discount'] > 0): ?>
            <div class="row"><span>Discount</span><span>-<?= h(rep_num($o['discount'])) ?></span></div>
          <?php endif; ?>
          <div class="grand"><span>TOTAL</span><span><?= h($cfg['cur']) ?> <?= h(rep_num($due)) ?></span></div>
          <?php if ($cfg['taxRate'] > 0): ?>
            <div class="row"><span><?= h($cfg['taxLabel']) ?> <?= h($cfg['taxPct']) ?>% (incl.)</span><span><?= h(rep_num($tax)) ?></span></div>
            <div class="row"><span>Net amount</span><span><?= h(rep_num($due - $tax)) ?></span></div>
          <?php endif; ?>

          <hr class="sep">
          <?php if ($pays): ?>
            <?php foreach ($pays as $p): ?>
              <div class="row"><span><?= $p['payment_method'] === 'mpesa' ? 'M-PESA' : 'CASH' ?></span><span><?= h(rep_num($p['amount'])) ?></span></div>
              <?php if ($p['payment_method'] === 'mpesa' && !empty($p['mpesa_receipt'])): ?>
                <div class="row"><span>M-Pesa Ref</span><span><?= h($p['mpesa_receipt']) ?></span></div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="row"><span><?= h(strtoupper((string) $o['payment_method'])) ?></span><span><?= h(rep_num($due)) ?></span></div>
          <?php endif; ?>
          <?php if ((float) $o['change_due'] > 0): ?>
            <div class="row"><span>Change</span><span><?= h(rep_num($o['change_due'])) ?></span></div>
          <?php endif; ?>

          <hr class="sep">
          <div class="bc" data-bc="<?= h($no) ?>"><small><?= h($no) ?></small></div>
          <div class="qr" data-qr="<?= h($qr) ?>"></div>
          <hr class="sep">
          <div class="foot"><?= nl2br(h($cfg['footer'])) ?></div>
          <div class="fine">Goods once sold are not returnable without this receipt.</div>
          <div class="fine">Reprinted <?= h(date('d/m/Y H:i')) ?></div>
        </article>
        <?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('rep_z_html')) {
    /** Printed summary for the selected period, cashier and method. */
    function rep_z_html(array $d, array $cfg, string $title, string $period, string $who, string $method): string
    {
        ob_start();
        ?>
        <article class="rc p<?= h($cfg['paper']) ?>">
          <div class="c">
            <div class="shop"><?= h($cfg['shop']) ?></div>
            <?php if ($cfg['addr'] !== ''): ?><div class="sub"><?= nl2br(h($cfg['addr'])) ?></div><?php endif; ?>
          </div>
          <hr class="sep">
          <div class="title"><?= h($title) ?></div>
          <hr class="sep">
          <div class="row"><span>Period</span><span><?= h($period) ?></span></div>
          <div class="row"><span>Staff</span><span><?= h($who) ?></span></div>
          <div class="row"><span>Payment</span><span><?= h($method) ?></span></div>
          <div class="row"><span>Printed</span><span><?= h(date('d/m/Y H:i')) ?></span></div>

          <hr class="sep">
          <div class="row"><span>Orders paid</span><span><?= (int) $d['n'] ?></span></div>
          <div class="row"><span>Gross</span><span><?= h(rep_num($d['gross'])) ?></span></div>
          <div class="row"><span>Discounts</span><span>-<?= h(rep_num($d['disc'])) ?></span></div>
          <div class="grand"><span>NET SALES</span><span><?= h($cfg['cur']) ?> <?= h(rep_num($d['net'])) ?></span></div>

          <hr class="sep">
          <div class="cols"><span>PAYMENTS</span><span>AMOUNT</span></div>
          <div class="row"><span>Cash</span><span><?= h(rep_num($d['collected']['cash'])) ?></span></div>
          <div class="row"><span>M-Pesa</span><span><?= h(rep_num($d['collected']['mpesa'])) ?></span></div>
          <div class="row"><span>Change given</span><span><?= h(rep_num($d['chg'])) ?></span></div>
          <div class="row"><span>Cancelled</span><span><?= (int) $d['cancelled_n'] ?> (<?= h(rep_num($d['cancelled_s'])) ?>)</span></div>

          <?php if ($d['staff']): ?>
            <hr class="sep">
            <div class="cols"><span>STAFF</span><span>NET</span></div>
            <?php foreach ($d['staff'] as $s): ?>
              <div class="row"><span><?= h($s['name']) ?> (<?= (int) $s['n'] ?>)</span><span><?= h(rep_num($s['net'])) ?></span></div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if ($d['products']): ?>
            <hr class="sep">
            <div class="cols"><span>ITEMS</span><span>QTY</span></div>
            <?php foreach (array_slice($d['products'], 0, 12) as $p): ?>
              <div class="row"><span><?= h($p['name']) ?></span><span><?= (int) $p['qty'] ?></span></div>
            <?php endforeach; ?>
          <?php endif; ?>

          <hr class="sep">
          <div class="row cnt"><span>Items sold</span><span><?= (int) array_sum(array_column($d['products'], 'qty')) ?></span></div>

          <div class="sig">Cashier signature</div>
          <div class="sig">Supervisor signature</div>
          <hr class="sep">
          <div class="foot"><?= nl2br(h($cfg['footer'])) ?></div>
        </article>
        <?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('rep_long_html')) {
    /** One continuous receipt: every paid order in the filter, then the totals. */
    function rep_long_html(array $packs, array $d, array $cfg, string $period, string $who, string $method): string
    {
        ob_start();
        ?>
        <article class="rc long p<?= h($cfg['paper']) ?>">
          <div class="c">
            <div class="shop"><?= h($cfg['shop']) ?></div>
            <?php if ($cfg['addr'] !== ''): ?><div class="sub"><?= nl2br(h($cfg['addr'])) ?></div><?php endif; ?>
            <?php if ($cfg['tel'] !== ''): ?><div class="sub">Tel: <?= h($cfg['tel']) ?></div><?php endif; ?>
          </div>
          <hr class="sep">
          <div class="title">Long Receipt</div>
          <hr class="sep">
          <div class="row"><span>Period</span><span><?= h($period) ?></span></div>
          <div class="row"><span>Staff</span><span><?= h($who) ?></span></div>
          <div class="row"><span>Payment</span><span><?= h($method) ?></span></div>
          <div class="row"><span>Printed</span><span><?= h(date('d/m/Y H:i')) ?></span></div>

          <?php foreach ($packs as $pack):
              $o    = $pack['o'];
              $due  = max(0.0, (float) $o['total'] - (float) $o['discount']);
              $no   = 'RCP-' . str_pad((string) $o['id'], 6, '0', STR_PAD_LEFT);
              $when = $o['paid_at'] ?: $o['created_at'];
              $pm   = match ($o['payment_method']) { 'cash' => 'Cash', 'mpesa' => 'M-Pesa', 'split' => 'Cash + M-Pesa', default => '-' };
          ?>
            <hr class="sep">
            <div class="row cnt"><span><?= h($no) ?></span><span><?= h(date('d/m H:i', strtotime($when))) ?></span></div>
            <div class="row"><span>Cashier</span><span><?= h($o['staff'] ?? '-') ?></span></div>
            <?php foreach ($pack['items'] as $it): ?>
              <div class="it">
                <div class="nm"><?= h($it['name']) ?></div>
                <div class="calc">
                  <span><?= (int) $it['qty'] ?> x <?= h(rep_num($it['price'])) ?></span>
                  <span><?= h(rep_num($it['price'] * $it['qty'])) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if ((float) $o['discount'] > 0): ?>
              <div class="row"><span>Discount</span><span>-<?= h(rep_num($o['discount'])) ?></span></div>
            <?php endif; ?>
            <div class="row cnt"><span>Total (<?= h($pm) ?>)</span><span><?= h(rep_num($due)) ?></span></div>
          <?php endforeach; ?>

          <hr class="sep">
          <div class="cols"><span>TOTALS</span><span>AMOUNT</span></div>
          <div class="row"><span>Orders paid</span><span><?= (int) $d['n'] ?></span></div>
          <div class="row"><span>Gross</span><span><?= h(rep_num($d['gross'])) ?></span></div>
          <div class="row"><span>Discounts</span><span>-<?= h(rep_num($d['disc'])) ?></span></div>
          <div class="grand"><span>NET</span><span><?= h($cfg['cur']) ?> <?= h(rep_num($d['net'])) ?></span></div>
          <div class="row"><span>Cash</span><span><?= h(rep_num($d['collected']['cash'])) ?></span></div>
          <div class="row"><span>M-Pesa</span><span><?= h(rep_num($d['collected']['mpesa'])) ?></span></div>
          <div class="row"><span>Change given</span><span><?= h(rep_num($d['chg'])) ?></span></div>
          <div class="row"><span>Items sold</span><span><?= (int) array_sum(array_column($d['products'], 'qty')) ?></span></div>

          <?php if ($d['products']): ?>
            <hr class="sep">
            <div class="cols"><span>TOP ITEMS</span><span>QTY</span></div>
            <?php foreach (array_slice($d['products'], 0, 15) as $p): ?>
              <div class="row"><span><?= h($p['name']) ?></span><span><?= (int) $p['qty'] ?></span></div>
            <?php endforeach; ?>
          <?php endif; ?>

          <hr class="sep">
          <div class="foot"><?= nl2br(h($cfg['footer'])) ?></div>
          <div class="fine">End of long receipt</div>
        </article>
        <?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('rep_print_document')) {
    function rep_print_document(string $body, array $cfg, bool $auto): void
    {
        $c39 = '{"0":"nnnwwnwnn","1":"wnnwnnnnw","2":"nnwwnnnnw","3":"wnwwnnnnn","4":"nnnwwnnnw","5":"wnnwwnnnn","6":"nnwwwnnnn","7":"nnnwnnwnw","8":"wnnwnnwnn","9":"nnwwnnwnn","A":"wnnnnwnnw","B":"nnwnnwnnw","C":"wnwnnwnnn","D":"nnnnwwnnw","E":"wnnnwwnnn","F":"nnwnwwnnn","G":"nnnnnwwnw","H":"wnnnnwwnn","I":"nnwnnwwnn","J":"nnnnwwwnn","K":"wnnnnnnww","L":"nnwnnnnww","M":"wnwnnnnwn","N":"nnnnwnnww","O":"wnnnwnnwn","P":"nnwnwnnwn","Q":"nnnnnnwww","R":"wnnnnnwwn","S":"nnwnnnwwn","T":"nnnnwnwwn","U":"wwnnnnnnw","V":"nwwnnnnnw","W":"wwwnnnnnn","X":"nwnnwnnnw","Y":"wwnnwnnnn","Z":"nwwnwnnnn","-":"nwnnnnwnw","*":"nwnnwnwnn"}';
        ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Print · <?= h($cfg['shop']) ?></title>
<style>
<?= rep_rc_css() ?>
@page{margin:0;size:<?= h($cfg['paper']) ?>mm auto}
html,body{margin:0;padding:0;background:#e5e7eb;font-family:Arial,Helvetica,sans-serif;color:#000}
.toolbar{position:sticky;top:0;z-index:5;display:flex;gap:8px;align-items:center;justify-content:center;flex-wrap:wrap;padding:10px;background:#111827}
.toolbar button,.toolbar a{padding:8px 14px;border-radius:6px;border:0;background:#4f46e5;color:#fff;font:600 13px Arial,sans-serif;text-decoration:none;cursor:pointer}
.toolbar a{background:#374151}
.sheets{display:flex;flex-direction:column;align-items:center;gap:14px;padding:14px 8px}
.rc{margin:0 auto;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.15)}
@media print{
  html,body{background:#fff}
  .toolbar{display:none!important}
  .sheets{padding:0;gap:0;display:block}
  .rc{box-shadow:none;page-break-after:always;break-after:page}
  .rc:last-child{page-break-after:auto;break-after:auto}
  .rc.long{page-break-after:auto!important;break-after:auto!important}
}
</style>
</head>
<body>
<div class="toolbar">
  <button type="button" onclick="window.print()">Print</button>
  <a href="javascript:window.close()">Close</a>
</div>
<main class="sheets"><?= $body ?></main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
(function () {
  var C39 = <?= $c39 ?>;

  function code39(txt) {
    var x = 0, r = '', s = '*' + String(txt).toUpperCase() + '*';
    for (var c = 0; c < s.length; c++) {
      var p = C39[s.charAt(c)];
      if (!p) continue;
      for (var i = 0; i < 9; i++) {
        var w = p.charAt(i) === 'w' ? 3 : 1;
        if (i % 2 === 0) r += '<rect x="' + x + '" y="0" width="' + w + '" height="40"/>';
        x += w;
      }
      x += 1;
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + x + ' 40" preserveAspectRatio="none" fill="#000">' + r + '</svg>';
  }

  document.querySelectorAll('[data-bc]').forEach(function (el) {
    try { el.insertAdjacentHTML('afterbegin', code39(el.getAttribute('data-bc'))); } catch (e) {}
  });

  document.querySelectorAll('[data-qr]').forEach(function (el) {
    try {
      if (typeof qrcode !== 'function') { el.hidden = true; return; }
      var q = qrcode(0, 'M');
      q.addData(el.getAttribute('data-qr'));
      q.make();
      el.innerHTML = q.createSvgTag({ cellSize: 3, margin: 0 });
    } catch (e) { el.hidden = true; }
  });

  <?php if ($auto): ?>
  window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });
  <?php endif; ?>
})();
</script>
</body>
</html>
        <?php
    }
}

/* ============================================================
   REQUEST
   ============================================================ */

$pdo   = db();
$tz    = new DateTimeZone(date_default_timezone_get());
$today = (new DateTimeImmutable('now', $tz))->setTime(0, 0);

$presets = [
    'today'     => 'Today',
    'yesterday' => 'Yesterday',
    'week'      => 'This week',
    'month'     => 'This month',
    'last30'    => 'Last 30 days',
    'custom'    => 'Custom range',
];
$preset = isset($presets[$_GET['range'] ?? '']) ? (string) $_GET['range'] : 'today';

$parseDate = static function (string $s) use ($tz): ?DateTimeImmutable {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s, $tz);
    return ($d && $d->format('Y-m-d') === $s) ? $d : null;
};

switch ($preset) {
    case 'yesterday': $from = $today->modify('-1 day'); $to = $from; break;
    case 'week':      $from = $today->modify('monday this week'); $to = $today; break;
    case 'month':     $from = $today->modify('first day of this month'); $to = $today; break;
    case 'last30':    $from = $today->modify('-29 days'); $to = $today; break;
    case 'custom':
        $from = $parseDate((string) ($_GET['from'] ?? '')) ?? $today;
        $to   = $parseDate((string) ($_GET['to'] ?? ''))   ?? $today;
        break;
    default:          $from = $today; $to = $today;
}
if ($to < $from) {
    [$from, $to] = [$to, $from];
}
if ($from->diff($to)->days > 366) {
    $to = $from->modify('+366 days');
}

$start = $from->format('Y-m-d 00:00:00');
$end   = $to->modify('+1 day')->format('Y-m-d 00:00:00');

$periodLabel = $from->format('Y-m-d') === $to->format('Y-m-d')
    ? $from->format('l, j M Y')
    : $from->format('j M Y') . ' – ' . $to->format('j M Y');

$staff     = rep_rows($pdo, "SELECT id, name, role FROM users WHERE active = 1 AND role IN ('cashier','waiter','admin') ORDER BY name");
$cashierId = (int) ($_GET['cashier'] ?? 0);
$staffIds  = array_map(fn($s) => (int) $s['id'], $staff);
if (!in_array($cashierId, $staffIds, true)) {
    $cashierId = 0;
}
$cashierName = 'All staff';
foreach ($staff as $s) {
    if ((int) $s['id'] === $cashierId) {
        $cashierName = (string) $s['name'];
    }
}

$methods = ['all' => 'All methods', 'cash' => 'Cash only', 'mpesa' => 'M-Pesa only', 'split' => 'Cash + M-Pesa'];
$method  = isset($methods[$_GET['method'] ?? '']) ? (string) $_GET['method'] : 'all';

$cw = $cashierId ? ' AND o.user_id = ?' : '';
$mw = $method !== 'all' ? ' AND o.payment_method = ?' : '';
$paidSql  = "o.status = 'paid' AND o.paid_at >= ? AND o.paid_at < ?" . $cw . $mw;
$paidArgs = array_merge([$start, $end], $cashierId ? [$cashierId] : [], $method !== 'all' ? [$method] : []);

$cfg = rep_cfg();
$d   = rep_collect($pdo, $paidSql, $paidArgs, $start, $end, $cashierId);

$baseParams = [
    'range'   => $preset,
    'from'    => $from->format('Y-m-d'),
    'to'      => $to->format('Y-m-d'),
    'cashier' => $cashierId ?: '',
    'method'  => $method !== 'all' ? $method : '',
];
$base  = BASE_URL . 'admin/reports';
$qsFor = fn(array $extra = []): string => $base . '?' . http_build_query(
    array_filter(array_merge($baseParams, $extra), fn($v) => $v !== '')
);

/* ---------- CSV export ---------- */

$export  = (string) ($_GET['export'] ?? '');
$fileTag = $from->format('Ymd') . '-' . $to->format('Ymd');

if ($export === 'orders') {
    $rows = rep_rows($pdo,
        "SELECT o.id, o.paid_at, o.payment_method, o.table_no, u.name AS staff,
                o.total, o.discount, (o.total - o.discount) AS net,
                o.amount_tendered, o.change_due, o.mpesa_ref
         FROM orders o LEFT JOIN users u ON u.id = o.user_id
         WHERE $paidSql ORDER BY o.paid_at", $paidArgs);
    rep_csv('orders-' . $fileTag,
        ['Receipt', 'Paid at', 'Method', 'Table', 'Cashier', 'Subtotal', 'Discount', 'Net', 'Tendered', 'Change', 'M-Pesa ref'],
        array_map(fn($r) => [
            'RCP-' . str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT), $r['paid_at'], $r['payment_method'],
            $r['table_no'], $r['staff'], $r['total'], $r['discount'], $r['net'],
            $r['amount_tendered'], $r['change_due'], $r['mpesa_ref'],
        ], $rows));
}

if ($export === 'products') {
    rep_csv('products-' . $fileTag, ['Product', 'Category', 'Qty sold', 'Revenue', 'Cost', 'Gross profit'],
        array_map(fn($r) => [
            $r['name'], $r['cat'], $r['qty'], $r['rev'], $r['cost'], (float) $r['rev'] - (float) $r['cost'],
        ], $d['products']));
}

/* ---------- Print ---------- */

if (!empty($_GET['print'])) {
    $scope = (string) ($_GET['scope'] ?? 'receipts');

    if ($scope === 'receipt') {
        $oid = (int) ($_GET['order'] ?? 0);
        $ok  = $oid ? rep_rows($pdo, "SELECT id FROM orders WHERE id = ? AND status = 'paid'", [$oid]) : [];
        $ids = $ok ? [$oid] : [];
    } else {
        $ids = array_map(
            fn($r) => (int) $r['id'],
            rep_rows($pdo, "SELECT o.id FROM orders o WHERE $paidSql ORDER BY o.paid_at LIMIT 1000", $paidArgs)
        );
    }

    $pack = rep_load_orders($pdo, $ids);
    $body = '';

    if ($scope === 'long') {
        $packs = [];
        foreach ($ids as $id) {
            if (isset($pack[$id])) {
                $packs[] = $pack[$id];
            }
        }
        $who  = $cashierId ? $cashierName : 'All staff';
        $body = rep_long_html($packs, $d, $cfg, $periodLabel, $who, $methods[$method]);
    } else {
        if (in_array($scope, ['summary', 'both'], true)) {
            $title = $cashierId ? 'Cashier summary' : 'Sales summary';
            $body .= rep_z_html($d, $cfg, $title, $periodLabel, $cashierName, $methods[$method]);
        }
        if ($scope !== 'summary') {
            foreach ($ids as $id) {
                if (isset($pack[$id])) {
                    $body .= rep_receipt_html($pack[$id], $cfg);
                }
            }
        }
    }

    if ($body === '') {
        $body = '<article class="rc p' . h($cfg['paper']) . '"><div class="title">Nothing to print</div><p class="c">No paid receipts match this selection.</p></article>';
    }

    rep_print_document($body, $cfg, !empty($_GET['autoprint']));
    exit;
}

/* ============================================================
   PAGE
   ============================================================ */

$pageTitle  = 'Reports';
$activePage = 'reports';
$crumbs     = ['Admin' => BASE_URL . 'admin', 'Reports' => null];

require APP_ROOT . '/app/shared/views/admin/header.php';

$maxHour  = max(1.0, (float) max(array_column($d['hourly'], 's')));
$maxCat   = max(1.0, (float) max(array_column($d['categories'], 'rev') ?: [0]));
$netShare = fn(float $v): float => $d['net'] > 0 ? round($v / $d['net'] * 100, 1) : 0.0;
$dbError  = !empty($GLOBALS['rep_db_error']);

$printAll     = $qsFor(['print' => 1, 'scope' => 'receipts', 'autoprint' => 1]);
$printSummary = $qsFor(['print' => 1, 'scope' => 'summary',  'autoprint' => 1]);
$printBoth    = $qsFor(['print' => 1, 'scope' => 'both',     'autoprint' => 1]);
$printLong    = $qsFor(['print' => 1, 'scope' => 'long',     'autoprint' => 1]);
?>

<style>
.rp-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
.rp-kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;display:flex;flex-direction:column;gap:6px;min-width:0}
.rp-kpi-l{font-size:12px;color:var(--text-3);font-weight:500}
.rp-kpi-v{font-size:20px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rp-kpi-s{font-size:12px;color:var(--text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rp-filter{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;align-items:end}
.rp-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.rp-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.rp-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.rp-in{width:100%;height:40px;padding:0 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;outline:none}
.rp-in:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.rp-in:disabled{opacity:.5}
.rp-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.rp-c4{grid-column:span 4}.rp-c6{grid-column:span 6}.rp-c8{grid-column:span 8}.rp-c12{grid-column:span 12}
.rp-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;gap:14px;min-width:0}
.rp-head{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap}
.rp-title{font-size:16px;margin-top:3px}
.rp-muted{font-size:12.5px;color:var(--text-3)}
.rp-bar{height:6px;border-radius:3px;background:var(--surface-2);overflow:hidden;min-width:60px}
.rp-bar>i{display:block;height:100%;border-radius:3px;background:var(--primary)}
.rp-hours{display:grid;grid-template-columns:repeat(24,minmax(0,1fr));gap:3px;align-items:end;height:170px;border-bottom:1px solid var(--border);padding-top:6px}
.rp-hour{display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;gap:4px;min-width:0}
.rp-hbar{width:100%;background:linear-gradient(180deg,#818cf8,var(--primary));border-radius:4px 4px 2px 2px}
.rp-hour span{font-size:10px;color:var(--text-3);height:12px}
.rp-tag{display:inline-block;padding:2px 8px;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:600}
.rp-sm{min-height:30px;padding:4px 10px;font-size:12.5px}
.rp-r{text-align:right}
.rp-strong{color:var(--text);font-weight:600}
.rp-notice{padding:12px 14px;border-radius:var(--radius);background:var(--warning-soft);color:var(--warning);border:1px solid var(--warning);font-weight:500}
.rp-mix{display:grid;gap:12px}
.rp-mix-row{display:grid;grid-template-columns:minmax(0,1.2fr) auto auto;gap:12px;align-items:center;font-size:13.5px}
.rp-split{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding-top:12px;border-top:1px solid var(--border)}
.rp-split small{display:block;font-size:11.5px;color:var(--text-3);text-transform:uppercase;letter-spacing:.04em}
.rp-split strong{font-size:15px;font-variant-numeric:tabular-nums}
.rp-share{min-width:120px}
@media (max-width:1280px){.rp-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:1024px){.rp-c4,.rp-c6,.rp-c8{grid-column:span 12}}
@media (max-width:640px){
  .rp-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
  .rp-hours{height:130px;gap:2px}
  .rp-hour span{font-size:9px}
  .rp-split{grid-template-columns:1fr}
  .rp-mix-row{grid-template-columns:1fr auto}
}
</style>

<!-- PAGE HEAD -->
<div class="page-head">
  <div>
    <p class="eyebrow">Analysis</p>
    <h1 class="page-title">Reports</h1>
    <p class="page-sub"><?= h($periodLabel) ?> · <?= h($cashierName) ?> · <?= h($methods[$method]) ?></p>
  </div>
  <div class="page-actions">
    <a class="btn btn--ghost" href="<?= h($qsFor(['export' => 'orders'])) ?>">Export orders CSV</a>
    <a class="btn btn--ghost" href="<?= h($qsFor(['export' => 'products'])) ?>">Export products CSV</a>
    <a class="btn btn--primary" href="<?= h($printLong) ?>" target="_blank" rel="noopener">Print long receipt</a>
  </div>
</div>

<?php if ($dbError): ?>
  <div class="rp-notice" role="alert">Some report data could not be loaded. Check the PHP error log. Profit needs the inventory migration (products.cost_price).</div>
<?php endif; ?>

<!-- FILTERS -->
<section class="card" aria-labelledby="flt-title">
  <div class="rp-head">
    <div>
      <p class="eyebrow">Filters</p>
      <h2 class="rp-title" id="flt-title">Choose what to report</h2>
    </div>
    <span class="rp-muted">Pick a cashier to see only their sales and receipts.</span>
  </div>

  <form method="get" action="<?= h($base) ?>" class="rp-filter" id="flt">
    <div class="rp-field">
      <label for="fRange">Period</label>
      <select class="rp-in" id="fRange" name="range">
        <?php foreach ($presets as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $k === $preset ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="rp-field">
      <label for="fFrom">From</label>
      <input class="rp-in" type="date" id="fFrom" name="from" value="<?= h($from->format('Y-m-d')) ?>" <?= $preset === 'custom' ? '' : 'disabled' ?>>
    </div>
    <div class="rp-field">
      <label for="fTo">To</label>
      <input class="rp-in" type="date" id="fTo" name="to" value="<?= h($to->format('Y-m-d')) ?>" <?= $preset === 'custom' ? '' : 'disabled' ?>>
    </div>
    <div class="rp-field">
      <label for="fCash">Cashier</label>
      <select class="rp-in" id="fCash" name="cashier">
        <option value="">All staff</option>
        <?php foreach ($staff as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $cashierId ? 'selected' : '' ?>><?= h($s['name']) ?> (<?= h($s['role']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="rp-field">
      <label for="fMeth">Payment</label>
      <select class="rp-in" id="fMeth" name="method">
        <?php foreach ($methods as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $k === $method ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="rp-actions">
      <button class="btn btn--primary" type="submit">Apply</button>
      <a class="btn btn--ghost" href="<?= h($base) ?>">Reset</a>
    </div>
  </form>

  <div class="rp-actions">
    <span class="rp-muted" style="margin-right:4px">Print:</span>
    <a class="btn btn--primary rp-sm" href="<?= h($printLong) ?>" target="_blank" rel="noopener">Long receipt</a>
    <a class="btn btn--ghost rp-sm" href="<?= h($printAll) ?>" target="_blank" rel="noopener">All receipts (<?= (int) $d['n'] ?>)</a>
    <a class="btn btn--ghost rp-sm" href="<?= h($printSummary) ?>" target="_blank" rel="noopener">Summary sheet</a>
    <a class="btn btn--ghost rp-sm" href="<?= h($printBoth) ?>" target="_blank" rel="noopener">Summary and receipts</a>
  </div>
</section>

<!-- KPIs -->
<section class="rp-kpis" aria-label="Key figures">
  <?php
  $kpis = [
      ['Net sales', money($d['net']), 'Paid, after discounts'],
      ['Orders paid', (string) $d['n'], 'Average ' . money($d['avg'])],
      ['Gross sales', money($d['gross']), 'Before discounts'],
      ['Discounts', money($d['disc']), $d['gross'] > 0 ? round($d['disc'] / $d['gross'] * 100, 1) . '% of gross' : '—'],
      ['Gross profit (est.)', money($d['profit']), 'Net minus product cost'],
      ['Cancelled', (string) $d['cancelled_n'], money($d['cancelled_s']) . ' value'],
  ];
  foreach ($kpis as [$label, $value, $sub]): ?>
    <article class="rp-kpi">
      <span class="rp-kpi-l"><?= h($label) ?></span>
      <span class="rp-kpi-v"><?= h($value) ?></span>
      <span class="rp-kpi-s"><?= h($sub) ?></span>
    </article>
  <?php endforeach; ?>
</section>

<div class="rp-grid">

  <!-- PAYMENTS -->
  <section class="rp-card rp-c6" aria-labelledby="pay-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Payments</p>
        <h2 class="rp-title" id="pay-title">How customers paid</h2>
      </div>
    </div>

    <div class="rp-mix">
      <?php
      $mixRows = [
          ['cash',  'Cash only',     '#34d399'],
          ['mpesa', 'M-Pesa only',   '#38bdf8'],
          ['split', 'Cash + M-Pesa', '#a78bfa'],
      ];
      foreach ($mixRows as [$key, $label, $color]):
          $pct = $netShare($d['mix'][$key]['s']);
      ?>
        <div class="rp-mix-row">
          <div>
            <div class="rp-strong"><?= h($label) ?> <span class="rp-muted">· <?= (int) $d['mix'][$key]['n'] ?> order(s)</span></div>
            <div class="rp-bar"><i style="width:<?= h($pct) ?>%;background:<?= h($color) ?>"></i></div>
          </div>
          <span class="rp-strong num"><?= h(money($d['mix'][$key]['s'])) ?></span>
          <span class="rp-muted num"><?= h($pct) ?>%</span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="rp-split">
      <div><small>Cash collected</small><strong><?= h(money($d['collected']['cash'])) ?></strong></div>
      <div><small>M-Pesa collected</small><strong><?= h(money($d['collected']['mpesa'])) ?></strong></div>
      <div><small>Change given</small><strong><?= h(money($d['chg'])) ?></strong></div>
    </div>
  </section>

  <!-- M-PESA STATUS -->
  <section class="rp-card rp-c6" aria-labelledby="mp-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">M-Pesa</p>
        <h2 class="rp-title" id="mp-title">Prompt results</h2>
      </div>
      <span class="rp-muted">Attempts in this period</span>
    </div>
    <div class="rp-split" style="grid-template-columns:repeat(4,minmax(0,1fr));border-top:0;padding-top:0">
      <div><small>Confirmed</small><strong><?= (int) $d['pstats']['success'] ?></strong></div>
      <div><small>Failed</small><strong><?= (int) $d['pstats']['failed'] ?></strong></div>
      <div><small>Needs review</small><strong style="color:<?= $d['pstats']['review'] > 0 ? 'var(--danger)' : 'inherit' ?>"><?= (int) $d['pstats']['review'] ?></strong></div>
      <div><small>Waiting</small><strong><?= (int) $d['pstats']['waiting'] ?></strong></div>
    </div>
    <?php
    $mpTotal = array_sum($d['pstats']);
    $okRate  = $mpTotal ? (int) round($d['pstats']['success'] / $mpTotal * 100) : 0;
    ?>
    <div>
      <div class="rp-bar"><i style="width:<?= $okRate ?>%;background:var(--success)"></i></div>
      <p class="rp-muted" style="margin-top:6px"><?= $okRate ?>% of M-Pesa attempts confirmed.</p>
    </div>
  </section>

  <!-- HOURLY -->
  <section class="rp-card rp-c12" aria-labelledby="hour-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Trading hours</p>
        <h2 class="rp-title" id="hour-title">Sales by hour</h2>
      </div>
      <span class="rp-muted">Peak trading time is the tallest bar</span>
    </div>
    <div class="rp-hours" role="img" aria-label="Sales by hour">
      <?php foreach ($d['hourly'] as $hr => $v): ?>
        <div class="rp-hour" title="<?= sprintf('%02d', $hr) ?>:00 · <?= h(money($v['s'])) ?> · <?= (int) $v['n'] ?> order(s)">
          <div class="rp-hbar" style="height:<?= $v['s'] > 0 ? max(3, round($v['s'] / $maxHour * 100)) : 0 ?>%"></div>
          <span><?= $hr % 3 === 0 ? sprintf('%02d', $hr) : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- DAILY -->
  <section class="rp-card rp-c6" aria-labelledby="day-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Daily</p>
        <h2 class="rp-title" id="day-title">Sales by day</h2>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Date</th><th class="rp-r">Orders</th><th class="rp-r">Net</th><th class="rp-r">Cash</th><th class="rp-r">M-Pesa</th><th class="rp-r">Split</th><th class="rp-r">Print</th></tr></thead>
        <tbody>
          <?php if (!$d['daily']): ?><tr><td colspan="7" class="empty">No paid orders in this period.</td></tr><?php endif; ?>
          <?php foreach ($d['daily'] as $r):
              $dayPrint = $qsFor(['range' => 'custom', 'from' => $r['d'], 'to' => $r['d'], 'print' => 1, 'scope' => 'long', 'autoprint' => 1]);
          ?>
            <tr>
              <td class="rp-strong"><?= h(date('D j M Y', strtotime($r['d']))) ?></td>
              <td class="rp-r num"><?= (int) $r['n'] ?></td>
              <td class="rp-r num rp-strong"><?= h(money($r['net'])) ?></td>
              <td class="rp-r num"><?= h(money($r['cash'])) ?></td>
              <td class="rp-r num"><?= h(money($r['mpesa'])) ?></td>
              <td class="rp-r num"><?= h(money($r['split'])) ?></td>
              <td class="rp-r"><a class="btn btn--ghost rp-sm" href="<?= h($dayPrint) ?>" target="_blank" rel="noopener">Long receipt</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- CASHIERS -->
  <section class="rp-card rp-c12" aria-labelledby="staff-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Staff</p>
        <h2 class="rp-title" id="staff-title">Cashier performance</h2>
      </div>
      <span class="rp-muted">Print a cashier's long receipt or summary for the selected period</span>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Cashier</th><th class="rp-r">Orders</th><th class="rp-r">Net sales</th><th class="rp-r">Average</th>
            <th class="rp-r">Discounts</th><th class="rp-r">Split orders</th><th>Share</th><th class="rp-r">Print</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$d['staff']): ?><tr><td colspan="8" class="empty">No paid orders in this period.</td></tr><?php endif; ?>
          <?php foreach ($d['staff'] as $s):
              $sid    = (int) $s['id'];
              $sLong  = $qsFor(['cashier' => $sid, 'print' => 1, 'scope' => 'long', 'autoprint' => 1]);
              $sRecs  = $qsFor(['cashier' => $sid, 'print' => 1, 'scope' => 'receipts', 'autoprint' => 1]);
              $share  = $d['net'] > 0 ? round((float) $s['net'] / $d['net'] * 100, 1) : 0;
          ?>
            <tr>
              <td class="rp-strong"><?= h($s['name']) ?></td>
              <td class="rp-r num"><?= (int) $s['n'] ?></td>
              <td class="rp-r num rp-strong"><?= h(money($s['net'])) ?></td>
              <td class="rp-r num"><?= h(money($s['avg'])) ?></td>
              <td class="rp-r num"><?= h(money($s['disc'])) ?></td>
              <td class="rp-r num"><?= (int) $s['split_n'] ?></td>
              <td class="rp-share"><div class="rp-bar"><i style="width:<?= h($share) ?>%"></i></div><span class="rp-muted num"><?= h($share) ?>%</span></td>
              <td class="rp-r">
                <div class="rp-actions" style="justify-content:flex-end">
                  <a class="btn btn--primary rp-sm" href="<?= h($sLong) ?>" target="_blank" rel="noopener">Long receipt</a>
                  <a class="btn btn--ghost rp-sm" href="<?= h($sRecs) ?>" target="_blank" rel="noopener">Receipts</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- PRODUCTS -->
  <section class="rp-card rp-c8" aria-labelledby="prod-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Menu</p>
        <h2 class="rp-title" id="prod-title">Product sales</h2>
      </div>
      <span class="rp-muted">Top 50 by revenue</span>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Product</th><th>Category</th><th class="rp-r">Qty</th><th class="rp-r">Revenue</th><th class="rp-r">Cost</th><th class="rp-r">Profit</th></tr></thead>
        <tbody>
          <?php if (!$d['products']): ?><tr><td colspan="7" class="empty">No product sales in this period.</td></tr><?php endif; ?>
          <?php foreach ($d['products'] as $i => $p):
              $profit = (float) $p['rev'] - (float) $p['cost']; ?>
            <tr>
              <td class="muted num"><?= $i + 1 ?></td>
              <td class="rp-strong"><?= h($p['name']) ?></td>
              <td><span class="rp-tag"><?= h($p['cat']) ?></span></td>
              <td class="rp-r num"><?= (int) $p['qty'] ?></td>
              <td class="rp-r num rp-strong"><?= h(money($p['rev'])) ?></td>
              <td class="rp-r num"><?= h(money($p['cost'])) ?></td>
              <td class="rp-r num" style="color:<?= $profit < 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= h(money($profit)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- CATEGORIES -->
  <section class="rp-card rp-c4" aria-labelledby="cat-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Categories</p>
        <h2 class="rp-title" id="cat-title">Sales by category</h2>
      </div>
    </div>
    <?php if (!$d['categories']): ?><p class="empty">No sales in this period.</p><?php endif; ?>
    <?php foreach ($d['categories'] as $c): ?>
      <div style="display:flex;flex-direction:column;gap:6px">
        <div style="display:flex;justify-content:space-between;gap:10px;font-size:13.5px">
          <span class="rp-strong"><?= h($c['cat']) ?> <span class="rp-muted">· <?= (int) $c['qty'] ?> sold</span></span>
          <span class="num"><?= h(money($c['rev'])) ?></span>
        </div>
        <div class="rp-bar"><i style="width:<?= round((float) $c['rev'] / $maxCat * 100, 1) ?>%"></i></div>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- ORDERS -->
  <section class="rp-card rp-c12" aria-labelledby="ord-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Receipts</p>
        <h2 class="rp-title" id="ord-title">Paid orders</h2>
      </div>
      <span class="rp-muted">Latest 300 in this filter. Export CSV for the full list.</span>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Receipt</th><th>Paid</th><th>Cashier</th><th>Table</th><th>Payment</th><th class="rp-r">Net</th><th class="rp-r">Print</th></tr></thead>
        <tbody>
          <?php if (!$d['orders']): ?><tr><td colspan="7" class="empty">No paid orders in this period.</td></tr><?php endif; ?>
          <?php foreach ($d['orders'] as $o):
              $one = $qsFor(['print' => 1, 'scope' => 'receipt', 'order' => (int) $o['id'], 'autoprint' => 1]); ?>
            <tr>
              <td class="rp-strong">RCP-<?= str_pad((string) $o['id'], 6, '0', STR_PAD_LEFT) ?></td>
              <td class="muted num"><?= h(date('j M, H:i', strtotime($o['paid_at']))) ?></td>
              <td><?= h($o['staff'] ?? '—') ?></td>
              <td><?= h($o['table_no'] ?: '—') ?></td>
              <td><?= h(match ($o['payment_method']) { 'cash' => 'Cash', 'mpesa' => 'M-Pesa', 'split' => 'Cash + M-Pesa', default => '—' }) ?></td>
              <td class="rp-r num rp-strong"><?= h(money((float) $o['total'] - (float) $o['discount'])) ?></td>
              <td class="rp-r"><a class="btn btn--ghost rp-sm" href="<?= h($one) ?>" target="_blank" rel="noopener">Receipt</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- M-PESA LOG -->
  <section class="rp-card rp-c12" aria-labelledby="log-title">
    <div class="rp-head">
      <div>
        <p class="eyebrow">Transactions</p>
        <h2 class="rp-title" id="log-title">M-Pesa attempts</h2>
      </div>
      <span class="rp-muted">Latest 100</span>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Time</th><th>Receipt</th><th>Cashier</th><th class="rp-r">Amount</th><th>Status</th><th>M-Pesa ref</th><th>Message</th></tr></thead>
        <tbody>
          <?php if (!$d['payments']): ?><tr><td colspan="7" class="empty">No M-Pesa attempts in this period.</td></tr><?php endif; ?>
          <?php foreach ($d['payments'] as $p):
              $tone = match ($p['status']) {
                  'success' => 'ok', 'failed' => 'bad', 'review' => 'review',
                  'pending', 'unknown' => 'warn', default => 'muted',
              }; ?>
            <tr>
              <td class="muted num"><?= h(date('j M, H:i', strtotime($p['created_at']))) ?></td>
              <td class="num">RCP-<?= str_pad((string) $p['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
              <td><?= h($p['staff'] ?? '—') ?></td>
              <td class="rp-r num"><?= h(money($p['amount'])) ?></td>
              <td><span class="badge badge--<?= h($tone) ?>"><?= h(ucfirst((string) $p['status'])) ?></span></td>
              <td class="mono muted"><?= h($p['mpesa_receipt'] ?: '—') ?></td>
              <td class="muted" style="white-space:normal;min-width:180px"><?= h($p['response_message'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>

<script>
/* Period dates are only editable for a custom range. Disabled fields are not submitted, so presets apply. */
(function () {
  var sel = document.getElementById('fRange');
  if (!sel) return;
  var from = document.getElementById('fFrom');
  var to   = document.getElementById('fTo');
  sel.addEventListener('change', function () {
    var custom = sel.value === 'custom';
    from.disabled = !custom;
    to.disabled = !custom;
    if (custom) from.focus();
  });
  document.getElementById('flt').addEventListener('submit', function () {
    if (sel.value !== 'custom') { from.disabled = true; to.disabled = true; }
  });
})();
</script>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>