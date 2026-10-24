<?php
/* Admin dashboard: live POS figures in the Adminator layout. Uses header.php and footer.php. */

$pageTitle   = 'Dashboard';
$activePage  = 'dashboard';
$crumbs      = ['Admin' => null];
$pageRefresh = 60;

require APP_ROOT . '/app/shared/views/admin/header.php';

$pdo = db();

/* ---------- Query helpers ---------- */

if (!function_exists('dash_rows')) {
    function dash_rows(PDO $pdo, string $sql, array $params = []): array
    {
        try {
            $s = $pdo->prepare($sql);
            $s->execute($params);
            return $s->fetchAll();
        } catch (PDOException $e) {
            error_log('Dashboard query failed: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('dash_val')) {
    function dash_val(PDO $pdo, string $sql, array $params = [], $default = 0)
    {
        $rows = dash_rows($pdo, $sql, $params);
        if (!$rows) {
            return $default;
        }
        $v = reset($rows[0]);
        return ($v === null || $v === false) ? $default : $v;
    }
}

if (!function_exists('dash_pill')) {
    function dash_pill(?float $pct): string
    {
        if ($pct === null) return '<span class="db-pill db-pill--info">New</span>';
        if ($pct > 0)      return '<span class="db-pill db-pill--up">▲ ' . h(abs($pct)) . '%</span>';
        if ($pct < 0)      return '<span class="db-pill db-pill--down">▼ ' . h(abs($pct)) . '%</span>';
        return '<span class="db-pill db-pill--flat">No change</span>';
    }
}

if (!function_exists('dash_badge')) {
    function dash_badge(string $status, string $title = ''): string
    {
        $map = [
            'success'   => ['ok', 'Paid'],
            'paid'      => ['ok', 'Paid'],
            'pending'   => ['warn', 'Waiting'],
            'unknown'   => ['warn', 'Checking'],
            'failed'    => ['bad', 'Failed'],
            'review'    => ['review', 'Review'],
            'open'      => ['warn', 'Open'],
            'held'      => ['warn', 'Held'],
            'cancelled' => ['muted', 'Cancelled'],
        ];
        [$tone, $label] = $map[$status] ?? ['muted', ucfirst($status)];
        return '<span class="db-badge db-badge--' . $tone . '"'
            . ($title !== '' ? ' title="' . h($title) . '"' : '') . '>' . h($label) . '</span>';
    }
}

if (!function_exists('dash_method')) {
    function dash_method(?string $m): string
    {
        return match ($m) {
            'cash'  => 'Cash',
            'mpesa' => 'M-Pesa',
            'split' => 'Cash + M-Pesa',
            default => '—',
        };
    }
}

/* ---------- Period ---------- */

$ranges = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days'];
$range  = isset($ranges[$_GET['range'] ?? '']) ? $_GET['range'] : 'today';
$days   = ['today' => 1, '7d' => 7, '30d' => 30][$range];

$tz      = new DateTimeZone(date_default_timezone_get());
$now     = new DateTimeImmutable('now', $tz);
$todayAt = $now->setTime(0, 0);
$start   = $todayAt->modify('-' . ($days - 1) . ' days');
$end     = $todayAt->modify('+1 day');
$pStart  = $start->modify('-' . $days . ' days');

$fmt = fn(DateTimeInterface $d): string => $d->format('Y-m-d H:i:s');
$pr  = [$fmt($start), $fmt($end)];
$pp  = [$fmt($pStart), $fmt($start)];

$compareLabel = ['today' => 'vs yesterday', '7d' => 'vs previous 7 days', '30d' => 'vs previous 30 days'][$range];
$hour         = (int) $now->format('H');
$greet        = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$userName     = (string) ($_SESSION['user']['name'] ?? 'there');

/* ---------- Sales (paid orders) ---------- */

$salesFor = function (array $p) use ($pdo): array {
    $r = dash_rows($pdo,
        "SELECT COUNT(*) AS n, COALESCE(SUM(total - discount), 0) AS s
         FROM orders WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?", $p);
    return ['n' => (int) ($r[0]['n'] ?? 0), 's' => (float) ($r[0]['s'] ?? 0)];
};

$cur       = $salesFor($pr);
$prev      = $salesFor($pp);
$avg       = $cur['n']  ? $cur['s']  / $cur['n']  : 0.0;
$prevAvg   = $prev['n'] ? $prev['s'] / $prev['n'] : 0.0;
$netChange = pct_change($cur['s'], $prev['s']);

/* ---------- Payment mix ---------- */

$mix = [
    'cash'  => ['n' => 0, 's' => 0.0],
    'mpesa' => ['n' => 0, 's' => 0.0],
    'split' => ['n' => 0, 's' => 0.0],
];
foreach (dash_rows($pdo,
    "SELECT payment_method AS m, COUNT(*) AS n, COALESCE(SUM(total - discount), 0) AS s
     FROM orders WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?
     GROUP BY payment_method", $pr) as $r) {
    if (isset($mix[$r['m']])) {
        $mix[$r['m']] = ['n' => (int) $r['n'], 's' => (float) $r['s']];
    }
}
$mixTotal = array_sum(array_column($mix, 's'));
$mixCount = array_sum(array_column($mix, 'n'));

$splitParts = ['cash' => 0.0, 'mpesa' => 0.0];
foreach (dash_rows($pdo,
    "SELECT p.payment_method AS m, COALESCE(SUM(p.amount), 0) AS s
     FROM payments p JOIN orders o ON o.id = p.order_id
     WHERE o.status = 'paid' AND o.payment_method = 'split'
       AND o.paid_at >= ? AND o.paid_at < ? AND p.status = 'success'
     GROUP BY p.payment_method", $pr) as $r) {
    if (isset($splitParts[$r['m']])) {
        $splitParts[$r['m']] = (float) $r['s'];
    }
}
$cashCollected  = $mix['cash']['s']  + $splitParts['cash'];
$mpesaCollected = $mix['mpesa']['s'] + $splitParts['mpesa'];

$share = fn(float $s): float => $mixTotal > 0 ? round($s / $mixTotal * 100, 1) : 0.0;

/* ---------- Outstanding balances ---------- */

$partSql = "FROM orders o
            JOIN (SELECT order_id, SUM(amount) AS s FROM payments
                  WHERE status = 'success' GROUP BY order_id) p ON p.order_id = o.id
            WHERE o.status = 'open'";
$outstanding = (float) dash_val($pdo, "SELECT COALESCE(SUM(GREATEST(o.total - o.discount - p.s, 0)), 0) $partSql");
$partCount   = (int) dash_val($pdo, "SELECT COUNT(*) $partSql");

/* ---------- Gauge rates ---------- */

$mp = ['success' => 0, 'failed' => 0];
foreach (dash_rows($pdo,
    "SELECT status AS k, COUNT(*) AS n FROM payments
     WHERE payment_method = 'mpesa' AND status IN ('success','failed')
       AND created_at >= ? AND created_at < ? GROUP BY status", $pr) as $r) {
    $mp[$r['k']] = (int) $r['n'];
}
$mpTotal = $mp['success'] + $mp['failed'];
$mpRate  = $mpTotal ? (int) round($mp['success'] / $mpTotal * 100) : 0;

$oc = ['paid' => 0, 'cancelled' => 0];
foreach (dash_rows($pdo,
    "SELECT status AS k, COUNT(*) AS n FROM orders
     WHERE status IN ('paid','cancelled') AND created_at >= ? AND created_at < ? GROUP BY status", $pr) as $r) {
    $oc[$r['k']] = (int) $r['n'];
}
$ocTotal    = $oc['paid'] + $oc['cancelled'];
$completion = $ocTotal ? (int) round($oc['paid'] / $ocTotal * 100) : 0;

$splitPct = $mixCount ? (int) round($mix['split']['n'] / $mixCount * 100) : 0;

// Stroke offset for a ring with circumference 201.06
$ring = fn(float $p): float => round(201.06 * (1 - max(0, min(100, $p)) / 100), 2);

/* ---------- Live status ---------- */

$live = [
    'open'    => (int) dash_val($pdo, "SELECT COUNT(*) FROM orders WHERE status = 'open'"),
    'held'    => (int) dash_val($pdo, "SELECT COUNT(*) FROM orders WHERE status = 'held'"),
    'waiting' => (int) dash_val($pdo, "SELECT COUNT(*) FROM payments WHERE payment_method = 'mpesa' AND status IN ('pending','unknown')"),
    'stuck'   => (int) dash_val($pdo, "SELECT COUNT(*) FROM payments WHERE payment_method = 'mpesa' AND status IN ('pending','unknown') AND created_at < NOW() - INTERVAL 10 MINUTE"),
    'review'  => (int) dash_val($pdo, "SELECT COUNT(*) FROM payments WHERE status = 'review'"),
    'stale'   => (int) dash_val($pdo, "SELECT COUNT(*) FROM orders WHERE status = 'open' AND created_at < NOW() - INTERVAL 4 HOUR"),
    'staff'   => (int) dash_val($pdo, "SELECT COUNT(DISTINCT user_id) FROM orders WHERE status = 'open'"),
    'first'   => (string) dash_val($pdo, "SELECT MIN(paid_at) FROM orders WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?", $pr, ''),
];

/* ---------- Action items ---------- */

$actions = [];
if ($live['review'] > 0) {
    $actions[] = ['urgent', 'URGENT', $live['review'] . ' payment(s) need a manager check', legacy_url('admin', 'sales')];
}
if ($live['stuck'] > 0) {
    $actions[] = ['warn', 'CHECK', $live['stuck'] . ' M-Pesa payment(s) unconfirmed for over 10 minutes', legacy_url('admin', 'sales')];
}
if ($partCount > 0) {
    $actions[] = ['upcoming', 'BALANCE', $partCount . ' part-paid order(s) still owe ' . money($outstanding), BASE_URL . 'cashier'];
}
if ($live['stale'] > 0) {
    $actions[] = ['low', 'OLD', $live['stale'] . ' open order(s) older than 4 hours', BASE_URL . 'cashier'];
}
if ($live['held'] > 0) {
    $actions[] = ['low', 'HELD', $live['held'] . ' held order(s) waiting to be resumed', BASE_URL . 'cashier'];
}
if (!$actions) {
    $actions[] = ['done', 'ALL CLEAR', 'Nothing needs attention right now', null];
}

/* ---------- Sales chart ---------- */

$buckets = [];
if ($range === 'today') {
    for ($i = 0; $i < 24; $i++) {
        $buckets[$i] = ['label' => sprintf('%02d:00', $i), 'n' => 0, 's' => 0.0];
    }
    $chartSql   = "SELECT HOUR(paid_at) AS b, COUNT(*) AS n, COALESCE(SUM(total - discount), 0) AS s
                   FROM orders WHERE status = 'paid' AND paid_at >= ? AND paid_at < ? GROUP BY b";
    $labelEvery = 3;
} else {
    for ($i = 0; $i < $days; $i++) {
        $d = $start->modify('+' . $i . ' days');
        $buckets[$d->format('Y-m-d')] = [
            'label' => $d->format($days === 7 ? 'D j' : 'j M'),
            'n' => 0, 's' => 0.0,
        ];
    }
    $chartSql   = "SELECT DATE(paid_at) AS b, COUNT(*) AS n, COALESCE(SUM(total - discount), 0) AS s
                   FROM orders WHERE status = 'paid' AND paid_at >= ? AND paid_at < ? GROUP BY b";
    $labelEvery = $days === 7 ? 1 : 5;
}
foreach (dash_rows($pdo, $chartSql, $pr) as $r) {
    $k = $range === 'today' ? (int) $r['b'] : (string) $r['b'];
    if (isset($buckets[$k])) {
        $buckets[$k]['n'] = (int) $r['n'];
        $buckets[$k]['s'] = (float) $r['s'];
    }
}
$chartMax = max(1.0, (float) max(array_column($buckets, 's')));

$peak = ['label' => '—', 's' => 0.0];
foreach ($buckets as $b) {
    if ($b['s'] > $peak['s']) $peak = $b;
}

// SVG points for the area chart (600 x 200 viewBox)
$W = 600; $H = 200;
$vals   = array_values(array_column($buckets, 's'));
$labels = array_values(array_column($buckets, 'label'));
$nPts   = count($vals);
$pts    = [];
foreach ($vals as $i => $v) {
    $x = $nPts > 1 ? $i * $W / ($nPts - 1) : $W / 2;
    $y = $H - 8 - ($v / $chartMax) * ($H - 24);
    $pts[] = [round($x, 1), round($y, 1)];
}
$linePath = '';
foreach ($pts as $i => [$x, $y]) {
    $linePath .= ($i === 0 ? 'M' : ' L') . $x . ' ' . $y;
}
$areaPath = $nPts ? $linePath . ' L' . $W . ' ' . $H . ' L0 ' . $H . ' Z' : '';

/* ---------- Tables and feed ---------- */

$recentPay = dash_rows($pdo,
    "SELECT p.order_id, p.payment_method, p.amount, p.status, p.mpesa_receipt, p.created_at
     FROM payments p ORDER BY p.id DESC LIMIT 6");

$recentOrders = dash_rows($pdo,
    "SELECT o.id, o.status, o.payment_method, o.total, o.discount, o.created_at, u.name AS staff
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.id DESC LIMIT 6");

/* ---------- Hero sentence ---------- */

if ($netChange === null) {
    $changeText = 'no sales to compare yet';
} elseif ($netChange >= 0) {
    $changeText = 'up <strong>' . h(abs($netChange)) . '%</strong> ' . h($compareLabel);
} else {
    $changeText = 'down <strong>' . h(abs($netChange)) . '%</strong> ' . h($compareLabel);
}
?>

<style>
/* Dashboard styles. Uses the variables defined in header.php. */
.db-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap}
.db-hero-title{font-size:34px;line-height:1.15;letter-spacing:-.025em;margin-top:6px}
.db-accent{color:var(--primary)}
.db-hero-sub{color:var(--text-2);font-size:14.5px;margin-top:8px;max-width:640px}
.db-hero-sub strong{color:var(--text);font-weight:600}
.db-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

.db-seg{display:inline-flex;padding:3px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
.db-seg a{padding:6px 12px;border-radius:7px;font-size:13px;font-weight:500;color:var(--text-3)}
.db-seg a.is-on{background:var(--primary-soft);color:var(--primary);font-weight:600}

.db-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.db-kpi{background:linear-gradient(160deg,var(--surface),var(--surface-2));border:1px solid var(--border);border-radius:16px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;min-width:0}
.db-kpi-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.db-kpi-id{display:flex;align-items:center;gap:10px;min-width:0}
.db-ico{width:32px;height:32px;border-radius:9px;display:grid;place-items:center;flex:none}
.db-ico svg{width:16px;height:16px}
.db-ico--success{background:var(--success-soft);color:var(--success)}
.db-ico--primary{background:var(--primary-soft);color:var(--primary)}
.db-ico--purple{background:var(--purple-soft);color:var(--purple)}
.db-ico--warning{background:var(--warning-soft);color:var(--warning)}
.db-kpi-label{font-size:13px;color:var(--text-3);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.db-kpi-value{font-size:30px;font-weight:700;letter-spacing:-.03em;line-height:1.1;font-variant-numeric:tabular-nums}
.db-kpi-foot{font-size:12.5px;color:var(--text-3);display:flex;gap:6px;flex-wrap:wrap}
.db-kpi-foot strong{color:var(--text-2);font-weight:600}

.db-pill{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}
.db-pill--up{background:var(--success-soft);color:var(--success)}
.db-pill--down{background:var(--danger-soft);color:var(--danger)}
.db-pill--flat{background:var(--surface-2);color:var(--text-3)}
.db-pill--info{background:var(--info-soft);color:var(--info)}

.db-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.db-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:18px;min-width:0}
.db-span-12{grid-column:span 12}
.db-span-8{grid-column:span 8}
.db-span-6{grid-column:span 6}
.db-span-4{grid-column:span 4}

.db-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
.db-eyebrow{font-size:11px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--text-3)}
.db-card-title{font-size:17px;font-weight:700;margin-top:4px}
.db-link{font-size:12.5px;font-weight:600;color:var(--primary);white-space:nowrap}
.db-link:hover{text-decoration:underline}
.db-note{font-size:12.5px;color:var(--text-3)}

/* Payment methods */
.db-methods{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.db-method-name{font-size:13px;font-weight:500;color:var(--text-2);display:flex;align-items:center;gap:8px}
.db-dot{width:8px;height:8px;border-radius:50%;flex:none}
.db-method-val{display:flex;align-items:baseline;gap:8px;margin-top:6px;font-size:18px;font-weight:700;font-variant-numeric:tabular-nums}
.db-method-val small{font-size:12px;color:var(--text-3);font-weight:500}
.db-track{height:5px;border-radius:3px;background:var(--surface-2);margin-top:10px;overflow:hidden}
.db-fill{height:100%;border-radius:3px}
.db-divider{height:1px;background:var(--border)}

/* Gauges */
.db-gauges{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.db-gauge{display:flex;align-items:center;gap:14px}
.db-ring{position:relative;width:76px;height:76px;flex:none}
.db-ring svg{width:76px;height:76px;transform:rotate(-90deg)}
.db-ring-track{fill:none;stroke:var(--surface-2);stroke-width:8}
.db-ring-fill{fill:none;stroke-width:8;stroke-linecap:round}
.db-ring-pct{position:absolute;inset:0;display:grid;place-items:center;font-weight:700;font-size:14px;font-variant-numeric:tabular-nums}
.db-gauge-name{font-size:14px;font-weight:600}
.db-gauge-cap{font-size:12px;color:var(--text-3);margin-top:2px}

/* Chart */
.db-chart{position:relative;height:220px}
.db-chart svg{width:100%;height:100%;display:block}
.db-chart-labels{display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:var(--text-3)}
.db-chart-labels span{white-space:nowrap}
.db-foot{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding-top:14px;border-top:1px solid var(--border)}
.db-foot small{display:block;font-size:11.5px;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em}
.db-foot strong{font-size:16px;font-weight:700;font-variant-numeric:tabular-nums}

/* Action items */
.db-actions-list{display:flex;flex-direction:column;gap:2px}
.db-action{display:flex;align-items:center;gap:12px;padding:10px 8px;border-radius:10px;font-size:13.5px}
.db-action:hover{background:var(--surface-2)}
.db-action-text{flex:1;color:var(--text-2)}
.db-tag{font-size:10px;font-weight:700;letter-spacing:.05em;padding:3px 7px;border-radius:5px;white-space:nowrap}
.db-tag--urgent{background:var(--danger-soft);color:var(--danger)}
.db-tag--warn{background:var(--warning-soft);color:var(--warning)}
.db-tag--upcoming{background:var(--info-soft);color:var(--info)}
.db-tag--low{background:var(--surface-2);color:var(--text-3)}
.db-tag--done{background:var(--success-soft);color:var(--success)}

/* Till */
.db-till{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.db-till-item small{display:block;font-size:12px;color:var(--text-3)}
.db-till-item strong{font-size:18px;font-weight:700;font-variant-numeric:tabular-nums}

/* Table */
.db-table-wrap{overflow-x:auto}
.db-table{width:100%;border-collapse:collapse;font-size:13.5px}
.db-table th{text-align:left;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-3);padding:10px 8px;border-bottom:1px solid var(--border);white-space:nowrap}
.db-table td{padding:12px 8px;border-bottom:1px solid var(--border);color:var(--text-2);white-space:nowrap}
.db-table tr:last-child td{border-bottom:0}
.db-table tr:hover td{background:var(--surface-2)}
.db-table .r{text-align:right}
.db-table .strong{color:var(--text);font-weight:500}
.db-badge{display:inline-flex;padding:3px 9px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}
.db-badge--ok{background:var(--success-soft);color:var(--success)}
.db-badge--warn{background:var(--warning-soft);color:var(--warning)}
.db-badge--bad{background:var(--danger-soft);color:var(--danger)}
.db-badge--review{background:var(--purple-soft);color:var(--purple)}
.db-badge--muted{background:var(--surface-2);color:var(--text-3)}
.db-empty{padding:24px 8px;text-align:center;color:var(--text-3);font-size:13px}

/* Activity feed */
.db-feed{display:flex;flex-direction:column;gap:14px}
.db-msg{display:flex;gap:12px;align-items:flex-start}
.db-avatar{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-size:11px;font-weight:700;flex:none}
.db-msg-body{flex:1;min-width:0}
.db-msg-text{font-size:13.5px;color:var(--text-2)}
.db-msg-text strong{color:var(--text);font-weight:600}
.db-msg-time{font-size:11.5px;color:var(--text-3);margin-top:2px}

.db-print-only{display:none}

@media (max-width:1200px){
  .db-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
  .db-span-8,.db-span-6,.db-span-4{grid-column:span 12}
  .db-gauges{grid-template-columns:1fr}
}
@media (max-width:700px){
  .db-kpis{grid-template-columns:1fr}
  .db-hero-title{font-size:26px}
  .db-methods{grid-template-columns:1fr}
  .db-foot{grid-template-columns:repeat(2,minmax(0,1fr))}
}
</style>

<!-- HERO -->
<section class="db-hero">
  <div>
    <p class="db-eyebrow"><?= h($now->format('l, j F Y')) ?></p>
    <h1 class="db-hero-title"><?= h($greet) ?>, <span class="db-accent"><?= h($userName) ?></span></h1>
    <p class="db-hero-sub">
      Net sales are <?= $changeText ?>, with <strong><?= (int) $live['open'] ?></strong> open order(s)
      and <strong><?= (int) $live['waiting'] ?></strong> M-Pesa prompt(s) waiting.
    </p>
  </div>
  <div class="db-actions">
    <div class="db-seg" role="tablist" aria-label="Period">
      <?php foreach ($ranges as $key => $label): ?>
        <a class="<?= $key === $range ? 'is-on' : '' ?>" href="<?= h(BASE_URL . 'admin?range=' . $key) ?>"><?= h($label) ?></a>
      <?php endforeach; ?>
    </div>
    <button class="btn btn--ghost" type="button" onclick="window.print()"><?= icon('print') ?> Export</button>
    <a class="btn btn--primary" href="<?= h(legacy_url('admin', 'reports')) ?>">+ New report</a>
  </div>
</section>

<!-- KPIs -->
<section class="db-kpis" aria-label="Key figures">
  <article class="db-kpi">
    <div class="db-kpi-top">
      <div class="db-kpi-id">
        <div class="db-ico db-ico--success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <span class="db-kpi-label">Net sales</span>
      </div>
      <?= dash_pill($netChange) ?>
    </div>
    <div class="db-kpi-value"><?= h(money($cur['s'])) ?></div>
    <div class="db-kpi-foot"><?= h($compareLabel) ?> · <?= h(money($prev['s'])) ?></div>
  </article>

  <article class="db-kpi">
    <div class="db-kpi-top">
      <div class="db-kpi-id">
        <div class="db-ico db-ico--primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg></div>
        <span class="db-kpi-label">Orders paid</span>
      </div>
      <?= dash_pill(pct_change((float) $cur['n'], (float) $prev['n'])) ?>
    </div>
    <div class="db-kpi-value"><?= (int) $cur['n'] ?></div>
    <div class="db-kpi-foot"><?= h($compareLabel) ?> · <?= (int) $prev['n'] ?> previously</div>
  </article>

  <article class="db-kpi">
    <div class="db-kpi-top">
      <div class="db-kpi-id">
        <div class="db-ico db-ico--purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg></div>
        <span class="db-kpi-label">Average order</span>
      </div>
      <?= dash_pill(pct_change($avg, $prevAvg)) ?>
    </div>
    <div class="db-kpi-value"><?= h(money($avg)) ?></div>
    <div class="db-kpi-foot"><?= h($compareLabel) ?> · <?= h(money($prevAvg)) ?></div>
  </article>

  <article class="db-kpi">
    <div class="db-kpi-top">
      <div class="db-kpi-id">
        <div class="db-ico db-ico--warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <span class="db-kpi-label">Balance outstanding</span>
      </div>
      <?php if ($partCount > 0): ?>
        <span class="db-pill db-pill--info"><?= (int) $partCount ?> part-paid</span>
      <?php else: ?>
        <span class="db-pill db-pill--flat">None</span>
      <?php endif; ?>
    </div>
    <div class="db-kpi-value"><?= h(money($outstanding)) ?></div>
    <div class="db-kpi-foot">Open orders still waiting for payment</div>
  </article>
</section>

<div class="db-grid">

  <!-- PAYMENT METHODS + GAUGES -->
  <section class="db-card db-span-12" aria-labelledby="mix-title">
    <div class="db-card-head">
      <div>
        <p class="db-eyebrow">Payments</p>
        <h2 class="db-card-title" id="mix-title">How customers paid</h2>
      </div>
      <span class="db-note"><?= h($ranges[$range]) ?> · <?= (int) $mixCount ?> paid order(s)</span>
    </div>

    <div class="db-methods">
      <?php
      $methodRows = [
          ['cash',  'Cash only',     '#34d399'],
          ['mpesa', 'M-Pesa only',   '#38bdf8'],
          ['split', 'Cash + M-Pesa', '#a78bfa'],
      ];
      foreach ($methodRows as [$key, $label, $color]): ?>
        <div>
          <div class="db-method-name"><span class="db-dot" style="background:<?= h($color) ?>"></span><?= h($label) ?></div>
          <div class="db-method-val">
            <?= h(money($mix[$key]['s'])) ?>
            <small><?= h($share($mix[$key]['s'])) ?>% · <?= (int) $mix[$key]['n'] ?> order(s)</small>
          </div>
          <div class="db-track"><div class="db-fill" style="width:<?= h($share($mix[$key]['s'])) ?>%;background:<?= h($color) ?>"></div></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="db-foot" style="grid-template-columns:repeat(2,minmax(0,1fr))">
      <div><small>Cash collected</small><strong><?= h(money($cashCollected)) ?></strong></div>
      <div><small>M-Pesa collected</small><strong><?= h(money($mpesaCollected)) ?></strong></div>
    </div>

    <div class="db-divider"></div>

    <div class="db-gauges">
      <div class="db-gauge">
        <div class="db-ring">
          <svg viewBox="0 0 80 80"><circle class="db-ring-track" cx="40" cy="40" r="32"/>
            <circle class="db-ring-fill" cx="40" cy="40" r="32" stroke="#f87171" stroke-dasharray="201.06" stroke-dashoffset="<?= $ring($mpRate) ?>"/></svg>
          <span class="db-ring-pct"><?= (int) $mpRate ?>%</span>
        </div>
        <div>
          <div class="db-gauge-name">M-Pesa success</div>
          <div class="db-gauge-cap"><?= (int) $mp['success'] ?> confirmed, <?= (int) $mp['failed'] ?> failed</div>
        </div>
      </div>

      <div class="db-gauge">
        <div class="db-ring">
          <svg viewBox="0 0 80 80"><circle class="db-ring-track" cx="40" cy="40" r="32"/>
            <circle class="db-ring-fill" cx="40" cy="40" r="32" stroke="#38bdf8" stroke-dasharray="201.06" stroke-dashoffset="<?= $ring($completion) ?>"/></svg>
          <span class="db-ring-pct"><?= (int) $completion ?>%</span>
        </div>
        <div>
          <div class="db-gauge-name">Order completion</div>
          <div class="db-gauge-cap"><?= (int) $oc['paid'] ?> paid, <?= (int) $oc['cancelled'] ?> cancelled</div>
        </div>
      </div>

      <div class="db-gauge">
        <div class="db-ring">
          <svg viewBox="0 0 80 80"><circle class="db-ring-track" cx="40" cy="40" r="32"/>
            <circle class="db-ring-fill" cx="40" cy="40" r="32" stroke="#fbbf24" stroke-dasharray="201.06" stroke-dashoffset="<?= $ring($splitPct) ?>"/></svg>
          <span class="db-ring-pct"><?= (int) $splitPct ?>%</span>
        </div>
        <div>
          <div class="db-gauge-name">Split payments</div>
          <div class="db-gauge-cap">Orders paid part cash, part M-Pesa</div>
        </div>
      </div>
    </div>
  </section>

  <!-- SALES CHART -->
  <section class="db-card db-span-8" aria-labelledby="chart-title">
    <div class="db-card-head">
      <div>
        <p class="db-eyebrow">Performance</p>
        <h2 class="db-card-title" id="chart-title">Net sales</h2>
      </div>
      <span class="db-note"><?= $range === 'today' ? 'By hour' : 'By day' ?> · <?= h($ranges[$range]) ?></span>
    </div>

    <div class="db-chart" role="img" aria-label="Net sales chart">
      <svg viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none">
        <defs>
          <linearGradient id="dbArea" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0" stop-color="#6366f1" stop-opacity=".35"/>
            <stop offset="1" stop-color="#6366f1" stop-opacity="0"/>
          </linearGradient>
        </defs>
        <?php if ($areaPath): ?>
          <path d="<?= h($areaPath) ?>" fill="url(#dbArea)"/>
          <path d="<?= h($linePath) ?>" fill="none" style="stroke:var(--primary)" stroke-width="2.5" vector-effect="non-scaling-stroke"/>
        <?php endif; ?>
      </svg>
    </div>

    <div class="db-chart-labels">
      <?php foreach ($labels as $i => $lbl):
          if ($i % $labelEvery !== 0) continue; ?>
        <span><?= h($lbl) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="db-foot">
      <div><small>Orders paid</small><strong><?= (int) $cur['n'] ?></strong></div>
      <div><small>Average order</small><strong><?= h(money($avg)) ?></strong></div>
      <div><small>Peak</small><strong><?= h($peak['label']) ?></strong></div>
      <div><small>Peak sales</small><strong><?= h(money($peak['s'])) ?></strong></div>
    </div>
  </section>

  <!-- ACTION ITEMS -->
  <section class="db-card db-span-4" aria-labelledby="todo-title">
    <div class="db-card-head">
      <div>
        <p class="db-eyebrow">Attention</p>
        <h2 class="db-card-title" id="todo-title">Action items</h2>
      </div>
    </div>
    <div class="db-actions-list">
      <?php foreach ($actions as [$tone, $tag, $text, $url]): ?>
        <div class="db-action">
          <span class="db-action-text"><?= h($text) ?></span>
          <span class="db-tag db-tag--<?= h($tone) ?>"><?= h($tag) ?></span>
          <?php if ($url): ?><a class="db-link" href="<?= h($url) ?>" aria-label="Open">→</a><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- TILL RIGHT NOW -->
  <section class="db-card db-span-4" aria-labelledby="till-title">
    <div class="db-card-head">
      <div>
        <p class="db-eyebrow">Live</p>
        <h2 class="db-card-title" id="till-title">Till right now</h2>
      </div>
    </div>
    <div class="db-till">
      <div class="db-till-item"><small>Cash today</small><strong><?= h(money($cashCollected)) ?></strong></div>
      <div class="db-till-item"><small>M-Pesa today</small><strong><?= h(money($mpesaCollected)) ?></strong></div>
      <div class="db-till-item"><small>Open orders</small><strong><?= (int) $live['open'] ?></strong></div>
      <div class="db-till-item"><small>Staff on till</small><strong><?= (int) $live['staff'] ?></strong></div>
      <div class="db-till-item"><small>Held orders</small><strong><?= (int) $live['held'] ?></strong></div>
      <div class="db-till-item"><small>First sale</small><strong><?= $live['first'] ? h(date('H:i', strtotime($live['first']))) : '—' ?></strong></div>
    </div>
  </section>

  <!-- RECENT SALES -->
  <section class="db-card db-span-8" aria-labelledby="sales-title">
    <div class="db-card-head">
      <div>
        <p class="db-eyebrow">Commerce</p>
        <h2 class="db-card-title" id="sales-title">Recent sales</h2>
      </div>
      <a class="db-link" href="<?= h(legacy_url('admin', 'sales')) ?>">Check all sales →</a>
    </div>
    <div class="db-table-wrap">
      <table class="db-table">
        <thead>
          <tr><th>Order</th><th>Staff</th><th>Table</th><th>Payment</th><th>Status</th><th>Date</th><th class="r">Total</th></tr>
        </thead>
        <tbody>
          <?php if (!$recentOrders): ?>
            <tr><td colspan="7" class="db-empty">No orders yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td class="strong">#<?= (int) $o['id'] ?></td>
              <td><?= h($o['staff'] ?? '—') ?></td>
              <td><?= h($o['table_no'] ?? '—') ?></td>
              <td><?= h(dash_method($o['payment_method'])) ?></td>
              <td><?= dash_badge((string) $o['status']) ?></td>
              <td><?= h(date('j M, H:i', strtotime($o['created_at']))) ?></td>
              <td class="r strong"><?= h(money((float) $o['total'] - (float) $o['discount'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ACTIVITY -->
  <section class="db-card db-span-4" aria-labelledby="feed-title">
    <div class="db-card-head">
      <div>
        <p class="db-eyebrow">Activity</p>
        <h2 class="db-card-title" id="feed-title">Latest payments</h2>
      </div>
    </div>
    <div class="db-feed">
      <?php if (!$recentPay): ?>
        <p class="db-empty">No payments yet.</p>
      <?php endif; ?>
      <?php foreach ($recentPay as $p):
          $isMpesa = $p['payment_method'] === 'mpesa';
          $who     = $isMpesa ? 'M-Pesa' : 'Cash';
          $init    = $isMpesa ? 'MP' : 'CA';
          $tone    = $isMpesa ? 'background:var(--info-soft);color:var(--info)' : 'background:var(--success-soft);color:var(--success)';
          switch ($p['status']) {
              case 'success': $text = '<strong>' . h(money($p['amount'])) . '</strong> received by ' . h($who) . ' for order #' . (int) $p['order_id'] . ($p['mpesa_receipt'] ? ' · ' . h($p['mpesa_receipt']) : ''); break;
              case 'pending':
              case 'unknown': $text = 'Waiting for ' . h($who) . ' PIN on order #' . (int) $p['order_id']; break;
              case 'failed':  $text = h($who) . ' payment for order #' . (int) $p['order_id'] . ' did not complete'; break;
              case 'review':  $text = 'Order #' . (int) $p['order_id'] . ' payment needs a manager check'; break;
              default:        $text = h($who) . ' payment for order #' . (int) $p['order_id']; break;
          }
      ?>
        <div class="db-msg">
          <div class="db-avatar" style="<?= $tone ?>"><?= h($init) ?></div>
          <div class="db-msg-body">
            <div class="db-msg-text"><?= $text ?></div>
            <div class="db-msg-time"><?= h(date('j M, H:i', strtotime($p['created_at']))) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>