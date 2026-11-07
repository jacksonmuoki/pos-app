<?php
/* Admin: sales. Reports on paid orders in a date range.
   Revenue = orders.total of paid orders. Payment split = successful rows in payments.
   Exports: summary CSV, item-level CSV, and a printable summary (58 mm, 80 mm or A4). */

$pdo  = db();
$base = BASE_URL . 'admin?page=sales';

/* ---------- Date range (inclusive, YYYY-MM-DD) ---------- */

$isDate = function (string $s): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
        return false;
    }
    return checkdate((int) substr($s, 5, 2), (int) substr($s, 8, 2), (int) substr($s, 0, 4));
};

$today = date('Y-m-d');
$from  = (string) ($_GET['from'] ?? '');
$to    = (string) ($_GET['to'] ?? '');
if (!$isDate($from)) $from = date('Y-m-d', strtotime('-6 days'));
if (!$isDate($to))   $to   = $today;
if ($from > $to)     [$from, $to] = [$to, $from];
if ($to > $today)    $to   = $today;

$start = $from . ' 00:00:00';
$end   = date('Y-m-d 00:00:00', strtotime($to . ' +1 day'));

$link = fn(array $extra = []): string => $base . '&' . http_build_query(['from' => $from, 'to' => $to] + $extra);

$presets = [
    'Today'        => [$today, $today],
    'Yesterday'    => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
    'Last 7 days'  => [date('Y-m-d', strtotime('-6 days')), $today],
    'Last 30 days' => [date('Y-m-d', strtotime('-29 days')), $today],
    'This month'   => [date('Y-m-01'), $today],
    'Last month'   => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
];

/* Shared filter: paid orders inside the range. */
$W = "o.status = 'paid' AND o.paid_at >= ? AND o.paid_at < ?";
$P = [$start, $end];

/* ---------- Helpers ---------- */

/** Readable payment label. Falls back to the order's own method, then "Not recorded". */
function sl_method_label(?string $payments, ?string $orderMethod): string
{
    $map = ['cash' => 'Cash', 'mpesa' => 'M-Pesa'];
    $raw = trim((string) $payments) !== '' ? (string) $payments : trim((string) $orderMethod);
    if ($raw === '') {
        return 'Not recorded';
    }
    $labels = array_map(
        fn($m) => $map[$m] ?? ucfirst($m),
        array_filter(array_map('trim', explode(',', $raw)))
    );
    return implode(' + ', $labels);
}

/** Streams a CSV download with a UTF-8 BOM so Excel reads it correctly. */
function sl_csv(string $filename, array $header, iterable $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

/** All figures for the range. Used by the page and the printable summary. */
function sl_report(PDO $pdo, string $start, string $end): array
{
    $W = "o.status = 'paid' AND o.paid_at >= ? AND o.paid_at < ?";
    $P = [$start, $end];
    $r = [];

    $s = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(o.total),0) AS revenue, COALESCE(SUM(o.discount),0) AS discounts FROM orders o WHERE $W");
    $s->execute($P);
    $row = $s->fetch();
    $r['orders']    = (int) $row['n'];
    $r['revenue']   = (float) $row['revenue'];
    $r['discounts'] = (float) $row['discounts'];
    $r['avg']       = $r['orders'] > 0 ? $r['revenue'] / $r['orders'] : 0.0;

    $s = $pdo->prepare("SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE $W");
    $s->execute($P);
    $r['items'] = (int) $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'cancelled' AND created_at >= ? AND created_at < ?");
    $s->execute([$start, $end]);
    $r['cancelled'] = (int) $s->fetchColumn();

    // Successful payments on paid orders
    $s = $pdo->prepare(
        "SELECT p.payment_method, COALESCE(SUM(p.amount),0) AS amt
         FROM payments p JOIN orders o ON o.id = p.order_id
         WHERE p.status = 'success' AND $W
         GROUP BY p.payment_method"
    );
    $s->execute($P);
    $r['split'] = ['cash' => 0.0, 'mpesa' => 0.0];
    foreach ($s->fetchAll() as $x) {
        $r['split'][$x['payment_method']] = (float) $x['amt'];
    }
    $r['recorded']    = array_sum($r['split']);
    $r['not_recorded'] = max(0.0, $r['revenue'] - $r['recorded']);

    $s = $pdo->prepare(
        "SELECT DATE(o.paid_at) AS day, COUNT(*) AS orders, COALESCE(SUM(o.total),0) AS revenue
         FROM orders o WHERE $W GROUP BY DATE(o.paid_at) ORDER BY day DESC"
    );
    $s->execute($P);
    $r['daily'] = $s->fetchAll();

    $s = $pdo->prepare(
        "SELECT HOUR(o.paid_at) AS hr, COUNT(*) AS orders, COALESCE(SUM(o.total),0) AS revenue
         FROM orders o WHERE $W GROUP BY HOUR(o.paid_at) ORDER BY hr"
    );
    $s->execute($P);
    $r['hourly'] = $s->fetchAll();

    $peak = null;
    foreach ($r['hourly'] as $hr) {
        if ($peak === null || (float) $hr['revenue'] > (float) $peak['revenue']) {
            $peak = $hr;
        }
    }
    $r['peak'] = $peak;

    $s = $pdo->prepare(
        "SELECT oi.product_id, MAX(oi.name) AS name, SUM(oi.qty) AS qty, SUM(oi.qty * oi.price) AS rev
         FROM order_items oi JOIN orders o ON o.id = oi.order_id
         WHERE $W GROUP BY oi.product_id ORDER BY rev DESC LIMIT 10"
    );
    $s->execute($P);
    $r['top'] = $s->fetchAll();

    return $r;
}

/* ---------- CSV exports (run before any output) ---------- */

$export = (string) ($_GET['export'] ?? '');

if ($export === 'csv') {
    $st = $pdo->prepare(
        "SELECT o.id, o.paid_at, o.table_no, o.total, o.discount,
                (SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id = o.id) AS items,
                (SELECT GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method)
                   FROM payments WHERE order_id = o.id AND status = 'success') AS pay_methods,
                o.payment_method AS order_method
         FROM orders o WHERE $W ORDER BY o.paid_at"
    );
    $st->execute($P);
    $rows = [];
    foreach ($st as $r) {
        $rows[] = [
            $r['id'], $r['paid_at'], $r['table_no'] ?? '', $r['items'],
            number_format((float) $r['total'], 2, '.', ''),
            number_format((float) $r['discount'], 2, '.', ''),
            sl_method_label($r['pay_methods'], $r['order_method']),
        ];
    }
    sl_csv("sales_{$from}_to_{$to}.csv",
        ['Order', 'Paid at', 'Table', 'Items', 'Total', 'Discount', 'Payment'], $rows);
}

if ($export === 'items') {
    $st = $pdo->prepare(
        "SELECT o.id AS order_id, o.paid_at, oi.name, oi.qty, oi.price, (oi.qty * oi.price) AS line_total
         FROM order_items oi JOIN orders o ON o.id = oi.order_id
         WHERE $W ORDER BY o.paid_at, o.id, oi.id"
    );
    $st->execute($P);
    $rows = [];
    foreach ($st as $r) {
        $rows[] = [
            $r['order_id'], $r['paid_at'], $r['name'], $r['qty'],
            number_format((float) $r['price'], 2, '.', ''),
            number_format((float) $r['line_total'], 2, '.', ''),
        ];
    }
    sl_csv("sales_items_{$from}_to_{$to}.csv",
        ['Order', 'Paid at', 'Product', 'Qty', 'Unit price', 'Line total'], $rows);
}

/* ---------- Report data ---------- */

$rep = sl_report($pdo, $start, $end);

/* ---------- Printable summary (standalone page, no admin layout) ---------- */

if (($_GET['print'] ?? '') === '1') {
    $paper = (string) ($_GET['paper'] ?? '80');
    if (!in_array($paper, ['58', '80', 'A4'], true)) {
        $paper = '80';
    }

    $shop    = (string) setting('shop_name', 'My Store');
    $addr    = (string) setting('shop_address', '');
    $phone   = (string) setting('shop_phone', '');
    $footer  = (string) setting('receipt_footer', 'Thank you!');
    $cur     = (string) setting('currency', 'KES');

    $pageSize = $paper === 'A4' ? 'A4' : $paper . 'mm auto';
    $margin   = $paper === 'A4' ? '12mm' : '3mm';
    $wrapW    = $paper === '58' ? '48mm' : ($paper === '80' ? '72mm' : '186mm');
    $fontSize = $paper === 'A4' ? '13px' : '11px';
    $multiDay = count($rep['daily']) > 1;
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sales summary <?= h($from) ?> to <?= h($to) ?></title>
<style>
  @page { size: <?= h($pageSize) ?>; margin: <?= h($margin) ?>; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #f3f4f6; color: #000; font-family: "Courier New", ui-monospace, monospace; }
  .sheet { width: <?= h($wrapW) ?>; margin: 16px auto; background: #fff; padding: 10px; font-size: <?= h($fontSize) ?>; }
  .c { text-align: center; }
  .big { font-size: 1.25em; font-weight: 700; }
  .sm { font-size: .9em; color: #333; }
  hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
  .sec { font-weight: 700; margin-top: 6px; letter-spacing: .04em; }
  .row { display: flex; justify-content: space-between; gap: 6px; }
  .row span:last-child { text-align: right; white-space: nowrap; }
  .tot { font-weight: 700; }
  .noprint { text-align: center; margin: 12px 0; font-family: system-ui, sans-serif; }
  .noprint button, .noprint a { font: inherit; padding: 8px 14px; border: 1px solid #111; background: #fff; border-radius: 6px; text-decoration: none; color: #111; cursor: pointer; margin: 2px; }
  .noprint button.primary { background: #111; color: #fff; }
  @media print {
    body { background: #fff; }
    .sheet { margin: 0; width: 100%; padding: 0; }
    .noprint { display: none; }
  }
</style>
</head>
<body>

<div class="noprint">
  <button class="primary" onclick="window.print()">Print</button>
  <a href="<?= h($link(['paper' => '58'])) ?>&print=1">58 mm</a>
  <a href="<?= h($link(['paper' => '80'])) ?>&print=1">80 mm</a>
  <a href="<?= h($link(['paper' => 'A4'])) ?>&print=1">A4</a>
  <a href="<?= h($link()) ?>">Back to sales</a>
</div>

<div class="sheet">
  <div class="c">
    <div class="big"><?= h(strtoupper($shop)) ?></div>
    <?php if ($addr !== ''): ?><div class="sm"><?= nl2br(h($addr)) ?></div><?php endif; ?>
    <?php if ($phone !== ''): ?><div class="sm">Tel: <?= h($phone) ?></div><?php endif; ?>
    <div class="sec">SALES SUMMARY</div>
    <div class="sm"><?= h(date('d M Y', strtotime($from))) ?><?= $from !== $to ? ' to ' . h(date('d M Y', strtotime($to))) : '' ?></div>
  </div>

  <hr>
  <div class="sec">SALES</div>
  <div class="row"><span>Paid orders</span><span><?= (int) $rep['orders'] ?></span></div>
  <div class="row"><span>Items sold</span><span><?= (int) $rep['items'] ?></span></div>
  <div class="row"><span>Discounts</span><span><?= h(money($rep['discounts'])) ?></span></div>
  <div class="row"><span>Average order</span><span><?= h(money($rep['avg'])) ?></span></div>
  <div class="row"><span>Cancelled</span><span><?= (int) $rep['cancelled'] ?></span></div>
  <hr>
  <div class="row tot"><span>REVENUE</span><span><?= h(money($rep['revenue'])) ?></span></div>

  <hr>
  <div class="sec">PAYMENTS</div>
  <div class="row"><span>Cash</span><span><?= h(money($rep['split']['cash'])) ?></span></div>
  <div class="row"><span>M-Pesa</span><span><?= h(money($rep['split']['mpesa'])) ?></span></div>
  <?php if ($rep['not_recorded'] > 0): ?>
    <div class="row"><span>Not recorded</span><span><?= h(money($rep['not_recorded'])) ?></span></div>
  <?php endif; ?>

  <?php if ($rep['top']): ?>
  <hr>
  <div class="sec">TOP PRODUCTS</div>
  <?php foreach ($rep['top'] as $p): ?>
    <div class="row"><span><?= (int) $p['qty'] ?> x <?= h($p['name']) ?></span><span><?= h(money($p['rev'])) ?></span></div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($multiDay && $rep['daily']): ?>
  <hr>
  <div class="sec">BY DAY</div>
  <?php foreach ($rep['daily'] as $d): ?>
    <div class="row"><span><?= h(date('d M', strtotime($d['day']))) ?> (<?= (int) $d['orders'] ?>)</span><span><?= h(money($d['revenue'])) ?></span></div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($rep['peak']): ?>
  <hr>
  <div class="row"><span>Busiest hour</span><span><?= sprintf('%02d:00', (int) $rep['peak']['hr']) ?> (<?= h(money($rep['peak']['revenue'])) ?>)</span></div>
  <?php endif; ?>

  <hr>
  <div class="c sm">Printed <?= h(date('d M Y, H:i')) ?></div>
  <div class="c sm"><?= h($footer) ?></div>
</div>

<?php if (($_GET['autoprint'] ?? '') === '1'): ?>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
</body>
</html>
<?php
    exit;
}

/* ---------- Paid orders list (paginated) ---------- */

$per  = 25;
$page = max(1, (int) ($_GET['p'] ?? 1));

$s = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $W");
$s->execute($P);
$total = (int) $s->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$s = $pdo->prepare(
    "SELECT o.id, o.paid_at, o.table_no, o.total, o.discount, o.payment_method AS order_method,
            (SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id = o.id) AS items,
            (SELECT GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method)
               FROM payments WHERE order_id = o.id AND status = 'success') AS pay_methods
     FROM orders o
     WHERE $W
     ORDER BY o.paid_at DESC
     LIMIT $per OFFSET $off"
);
$s->execute($P);
$orders = $s->fetchAll();

$maxDayRev = 0.0;
foreach ($rep['daily'] as $d) {
    $maxDayRev = max($maxDayRev, (float) $d['revenue']);
}
$maxTopRev = 0.0;
foreach ($rep['top'] as $t) {
    $maxTopRev = max($maxTopRev, (float) $t['rev']);
}

$share = fn(float $amt): int => $rep['revenue'] > 0 ? (int) round($amt / $rep['revenue'] * 100) : 0;
$pctOf = fn(float $amt, float $of): int => $of > 0 ? (int) round($amt / $of * 100) : 0;

/* ---------- Layout ---------- */

$pageTitle  = 'Sales';
$activePage = 'sales';
$crumbs     = ['Admin' => BASE_URL . 'admin', 'Sales' => null];

require APP_ROOT . '/app/shared/views/admin/header.php';
?>

<style>
.sl-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
.sl-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;flex-direction:column;gap:6px;min-width:0}
.sl-stat-label{font-size:12.5px;color:var(--text-3);font-weight:500}
.sl-stat-value{font-size:22px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
.sl-stat-sub{font-size:12px;color:var(--text-3)}
.sl-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;gap:14px;min-width:0}
.sl-grid2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:14px;align-items:start}
.sl-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
.sl-title{font-size:17px;margin-top:4px}
.sl-muted{font-size:12.5px;color:var(--text-3)}
.sl-strong{color:var(--text);font-weight:600}
.sl-bar{height:8px;border-radius:999px;background:var(--surface-2);overflow:hidden;border:1px solid var(--border);min-width:60px}
.sl-bar i{display:block;height:100%;background:var(--primary)}
.sl-split{display:flex;flex-direction:column;gap:12px}
.sl-split-row{display:flex;flex-direction:column;gap:6px}
.sl-split-top{display:flex;justify-content:space-between;gap:10px;font-size:13.5px;color:var(--text-2)}
.sl-presets{display:flex;gap:6px;flex-wrap:wrap}
.sl-presets a{padding:6px 11px;border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-2);font-size:13px;text-decoration:none}
.sl-presets a.is-on{background:var(--primary-soft);color:var(--primary);border-color:var(--primary);font-weight:600}
.sl-filter{display:grid;grid-template-columns:repeat(2,minmax(0,200px)) auto auto;gap:12px;align-items:end}
.sl-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.sl-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.sl-input{width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;outline:none}
.sl-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.sl-btns{display:flex;gap:8px;flex-wrap:wrap}
.sl-btn-sm{min-height:32px;padding:5px 10px;font-size:12.5px}
.sl-pager{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;color:var(--text-3)}
.sl-pager nav{display:flex;gap:6px}
.sl-pager a{padding:6px 11px;border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-2);text-decoration:none}
.sl-tag{display:inline-block;padding:2px 9px;border-radius:999px;background:var(--surface-2);color:var(--text-2);font-size:12px;font-weight:600;border:1px solid var(--border)}
.sl-note{font-size:12.5px;color:var(--text-3);line-height:1.5}

@media (max-width:900px){ .sl-grid2{grid-template-columns:1fr} }
@media (max-width:560px){
  .sl-stat{padding:12px}
  .sl-stat-value{font-size:18px}
  .sl-filter{grid-template-columns:1fr 1fr}
  .sl-pager{justify-content:center;text-align:center}
}
</style>

<!-- Page head -->
<div class="page-head">
  <div>
    <p class="eyebrow">Reports</p>
    <h1 class="page-title">Sales</h1>
    <p class="page-sub">Paid orders for <?= h(date('d M Y', strtotime($from))) ?><?= $from !== $to ? ' to ' . h(date('d M Y', strtotime($to))) : '' ?>. Cancelled and open orders are not counted as sales.</p>
  </div>
  <div class="page-actions sl-btns">
    <a class="btn btn--ghost" href="<?= h($link(['print' => '1', 'paper' => '80'])) ?>" target="_blank" rel="noopener">Print summary</a>
    <a class="btn btn--ghost" href="<?= h($link(['export' => 'csv'])) ?>">Orders CSV</a>
    <a class="btn btn--ghost" href="<?= h($link(['export' => 'items'])) ?>">Items CSV</a>
  </div>
</div>

<!-- Date range -->
<section class="sl-card" aria-label="Date range">
  <div class="sl-presets">
    <?php foreach ($presets as $label => [$pf, $pt]):
        $on = $pf === $from && $pt === $to; ?>
      <a href="<?= h($base . '&' . http_build_query(['from' => $pf, 'to' => $pt])) ?>" class="<?= $on ? 'is-on' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <form method="get" action="<?= h($base) ?>" class="sl-filter">
    <input type="hidden" name="page" value="sales">
    <div class="sl-field">
      <label for="slFrom">From</label>
      <input class="sl-input" type="date" id="slFrom" name="from" value="<?= h($from) ?>" max="<?= h($today) ?>">
    </div>
    <div class="sl-field">
      <label for="slTo">To</label>
      <input class="sl-input" type="date" id="slTo" name="to" value="<?= h($to) ?>" max="<?= h($today) ?>">
    </div>
    <button class="btn btn--primary" type="submit">Apply</button>
    <a class="btn btn--ghost" href="<?= h($base) ?>">Reset</a>
  </form>
</section>

<!-- Summary -->
<section class="sl-stats" aria-label="Sales summary">
  <div class="sl-stat">
    <span class="sl-stat-label">Revenue</span>
    <span class="sl-stat-value"><?= h(money($rep['revenue'])) ?></span>
  </div>
  <div class="sl-stat">
    <span class="sl-stat-label">Paid orders</span>
    <span class="sl-stat-value"><?= (int) $rep['orders'] ?></span>
  </div>
  <div class="sl-stat">
    <span class="sl-stat-label">Average order</span>
    <span class="sl-stat-value"><?= h(money($rep['avg'])) ?></span>
  </div>
  <div class="sl-stat">
    <span class="sl-stat-label">Items sold</span>
    <span class="sl-stat-value"><?= (int) $rep['items'] ?></span>
  </div>
  <div class="sl-stat">
    <span class="sl-stat-label">Discounts given</span>
    <span class="sl-stat-value"><?= h(money($rep['discounts'])) ?></span>
  </div>
  <div class="sl-stat">
    <span class="sl-stat-label">Busiest hour</span>
    <span class="sl-stat-value"><?= $rep['peak'] ? sprintf('%02d:00', (int) $rep['peak']['hr']) : '–' ?></span>
    <?php if ($rep['peak']): ?><span class="sl-stat-sub"><?= (int) $rep['peak']['orders'] ?> order(s)</span><?php endif; ?>
  </div>
  <div class="sl-stat">
    <span class="sl-stat-label">Cancelled</span>
    <span class="sl-stat-value"><?= (int) $rep['cancelled'] ?></span>
    <span class="sl-stat-sub">Created in this range</span>
  </div>
</section>

<div class="sl-grid2">
  <!-- Payment split -->
  <section class="sl-card" aria-labelledby="pay-title">
    <div>
      <p class="eyebrow">Payments</p>
      <h2 id="pay-title" class="sl-title">How customers paid</h2>
    </div>

    <div class="sl-split">
      <?php
      $rowsPay = [
          ['Cash',         $rep['split']['cash'], 'var(--primary)'],
          ['M-Pesa',       $rep['split']['mpesa'], 'var(--primary)'],
          ['Not recorded', $rep['not_recorded'],   'var(--border)'],
      ];
      foreach ($rowsPay as [$label, $amt, $color]):
          $pct = $share((float) $amt);
      ?>
        <div class="sl-split-row">
          <div class="sl-split-top">
            <span class="sl-strong"><?= h($label) ?></span>
            <span><?= h(money($amt)) ?> <span class="sl-muted">(<?= $pct ?>%)</span></span>
          </div>
          <div class="sl-bar"><i style="width:<?= $pct ?>%;background:<?= $color ?>"></i></div>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="sl-note">"Not recorded" covers paid orders from before payment records were kept. Their revenue is still counted in the totals.</p>
  </section>

  <!-- Top products -->
  <section class="sl-card" aria-labelledby="top-title">
    <div>
      <p class="eyebrow">Products</p>
      <h2 id="top-title" class="sl-title">Top products by revenue</h2>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Product</th>
            <th class="r">Qty</th>
            <th class="r">Revenue</th>
            <th>Share</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rep['top']): ?>
            <tr><td colspan="4" class="empty">No sales in this range.</td></tr>
          <?php endif; ?>
          <?php foreach ($rep['top'] as $p):
              $bar = $pctOf((float) $p['rev'], $maxTopRev); ?>
            <tr>
              <td class="sl-strong"><?= h($p['name']) ?></td>
              <td class="r num"><?= (int) $p['qty'] ?></td>
              <td class="r num sl-strong"><?= h(money($p['rev'])) ?></td>
              <td><div class="sl-bar"><i style="width:<?= $bar ?>%"></i></div></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<!-- Daily breakdown -->
<section class="sl-card" aria-labelledby="day-title">
  <div class="sl-head">
    <div>
      <p class="eyebrow">Daily</p>
      <h2 id="day-title" class="sl-title">Sales by day</h2>
    </div>
    <span class="sl-muted"><?= count($rep['daily']) ?> day(s) with sales</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th class="r">Orders</th>
          <th class="r">Average order</th>
          <th class="r">Revenue</th>
          <th>Share of range</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rep['daily']): ?>
          <tr><td colspan="5" class="empty">No sales in this range.</td></tr>
        <?php endif; ?>
        <?php foreach ($rep['daily'] as $d):
            $dOrders = (int) $d['orders'];
            $dRev    = (float) $d['revenue'];
            $bar     = $pctOf($dRev, $maxDayRev);
        ?>
          <tr>
            <td><?= h(date('D, d M Y', strtotime($d['day']))) ?></td>
            <td class="r num"><?= $dOrders ?></td>
            <td class="r num"><?= h(money($dOrders > 0 ? $dRev / $dOrders : 0)) ?></td>
            <td class="r num sl-strong"><?= h(money($dRev)) ?></td>
            <td><div class="sl-bar"><i style="width:<?= $bar ?>%"></i></div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Hourly breakdown -->
<section class="sl-card" aria-labelledby="hour-title">
  <div class="sl-head">
    <div>
      <p class="eyebrow">Hours</p>
      <h2 id="hour-title" class="sl-title">Sales by hour</h2>
    </div>
    <span class="sl-muted">Useful for staffing and stock planning</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Hour</th>
          <th class="r">Orders</th>
          <th class="r">Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rep['hourly']): ?>
          <tr><td colspan="3" class="empty">No sales in this range.</td></tr>
        <?php endif; ?>
        <?php foreach ($rep['hourly'] as $hr): ?>
          <tr>
            <td><?= sprintf('%02d:00 – %02d:59', (int) $hr['hr'], (int) $hr['hr']) ?></td>
            <td class="r num"><?= (int) $hr['orders'] ?></td>
            <td class="r num sl-strong"><?= h(money($hr['revenue'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Orders -->
<section class="sl-card" aria-labelledby="ord-title">
  <div class="sl-head">
    <div>
      <p class="eyebrow">Orders</p>
      <h2 id="ord-title" class="sl-title">Paid orders</h2>
    </div>
    <span class="sl-muted"><?= $total ?> order(s)</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Paid at</th>
          <th>Table</th>
          <th class="r">Items</th>
          <th class="r">Discount</th>
          <th class="r">Total</th>
          <th>Payment</th>
          <th class="r">Receipt</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$orders): ?>
          <tr><td colspan="8" class="empty">No paid orders in this range.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="sl-strong">#<?= (int) $o['id'] ?></td>
            <td><?= h(date('d M Y, H:i', strtotime($o['paid_at']))) ?></td>
            <td><?= $o['table_no'] !== null && $o['table_no'] !== '' ? h($o['table_no']) : '<span class="sl-muted">–</span>' ?></td>
            <td class="r num"><?= (int) $o['items'] ?></td>
            <td class="r num"><?= (float) $o['discount'] > 0 ? h(money($o['discount'])) : '<span class="sl-muted">–</span>' ?></td>
            <td class="r num sl-strong"><?= h(money($o['total'])) ?></td>
            <td><span class="sl-tag"><?= h(sl_method_label($o['pay_methods'], $o['order_method'])) ?></span></td>
            <td class="r">
              <a class="btn btn--ghost sl-btn-sm" href="<?= h(BASE_URL . 'cashier?receipt=' . (int) $o['id']) ?>" target="_blank" rel="noopener">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="sl-pager">
      <span>Page <?= $page ?> of <?= $pages ?></span>
      <nav aria-label="Pages">
        <?php if ($page > 1): ?><a href="<?= h($link(['p' => $page - 1])) ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages): ?><a href="<?= h($link(['p' => $page + 1])) ?>">Next</a><?php endif; ?>
      </nav>
    </div>
  <?php endif; ?>
</section>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>