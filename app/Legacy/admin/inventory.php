<?php
/* Admin: inventory. Track stock, receive deliveries, record waste, count stock,
   set reorder levels and unit costs. Stock falls automatically when orders are paid. */

require_once APP_ROOT . '/app/includes/Inventory.php';

$pdo  = db();
$base = BASE_URL . 'admin/inventory';
$uid  = (int) ($_SESSION['user']['id'] ?? 0);

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/* ---------- Helpers ---------- */

function inv_flash(string $type, string $msg): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
}

function inv_go(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Quantity with up to 3 decimals. Returns null when invalid. */
function inv_qty(string $s): ?float
{
    $s = str_replace([',', ' '], '', trim($s));
    if ($s === '' || !preg_match('/^\d{1,9}(\.\d{1,3})?$/', $s)) {
        return null;
    }
    return round((float) $s, 3);
}

/** Money with up to 2 decimals. Returns null when invalid. */
function inv_money(string $s): ?float
{
    $s = str_replace([',', ' '], '', trim($s));
    if ($s === '' || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $s)) {
        return null;
    }
    return round((float) $s, 2);
}

/** Display a quantity without trailing zeros. */
function inv_fmt(?float $v): string
{
    if ($v === null) return '—';
    return rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
}

function inv_status(?float $qty, ?float $reorder): array
{
    if ($qty === null)                             return ['Not tracked', 'badge--muted'];
    if ($qty <= 0)                                 return ['Out of stock', 'badge--bad'];
    if ($reorder !== null && $qty <= $reorder)     return ['Low', 'badge--warn'];
    return ['In stock', 'badge--ok'];
}

/* ---------- POST actions ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $self = $base . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');

    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        inv_flash('error', 'Your session expired. Please try again.');
        inv_go($base);
    }

    $back = (string) ($_POST['return'] ?? $self);
    if (!str_starts_with($back, $base)) {
        $back = $base;
    }

    $action = (string) ($_POST['action'] ?? '');
    $pid    = (int) ($_POST['product_id'] ?? 0);

    try {
        $pdo->beginTransaction();

        switch ($action) {
            case 'receive':
                $qty = inv_qty((string) ($_POST['qty'] ?? ''));
                if ($qty === null || $qty <= 0) {
                    throw new InvalidArgumentException('Enter the quantity received, greater than zero.');
                }
                $note = trim((string) ($_POST['note'] ?? ''));
                Inventory::move($pdo, $pid, $qty, 'receive', $uid, $note !== '' ? $note : 'Stock received');

                $cost = inv_money((string) ($_POST['cost'] ?? ''));
                if ($cost !== null) {
                    $pdo->prepare('UPDATE products SET cost_price = ? WHERE id = ?')->execute([$cost, $pid]);
                }
                inv_flash('success', 'Received ' . inv_fmt($qty) . ' into stock.');
                break;

            case 'waste':
                $qty  = inv_qty((string) ($_POST['qty'] ?? ''));
                $note = trim((string) ($_POST['note'] ?? ''));
                if ($qty === null || $qty <= 0) {
                    throw new InvalidArgumentException('Enter the quantity wasted, greater than zero.');
                }
                if ($note === '') {
                    throw new InvalidArgumentException('Add a note explaining the waste, for example "expired" or "dropped".');
                }
                Inventory::move($pdo, $pid, -1 * $qty, 'waste', $uid, $note);
                inv_flash('success', 'Recorded ' . inv_fmt($qty) . ' as waste.');
                break;

            case 'count':
                $counted = inv_qty((string) ($_POST['qty'] ?? ''));
                if ($counted === null) {
                    throw new InvalidArgumentException('Enter the number you counted on the shelf.');
                }
                $current = Inventory::lockStock($pdo, $pid) ?? 0.0;
                $diff    = round($counted - $current, 3);
                if ($diff == 0.0) {
                    inv_flash('info', 'The count matches the system. No change made.');
                } else {
                    Inventory::move($pdo, $pid, $diff, 'count', $uid,
                        'Stock count: counted ' . inv_fmt($counted) . ', system had ' . inv_fmt($current));
                    inv_flash('success', 'Stock set to ' . inv_fmt($counted) . ' (' . ($diff > 0 ? '+' : '') . inv_fmt($diff) . ').');
                }
                break;

            case 'track':
                $current = Inventory::lockStock($pdo, $pid);
                if ($current === null) {
                    Inventory::move($pdo, $pid, 0.0, 'track', $uid, 'Stock tracking turned on');
                }
                inv_flash('success', 'Stock tracking is on for this product.');
                break;

            case 'reorder':
                $raw   = trim((string) ($_POST['level'] ?? ''));
                $level = $raw === '' ? null : inv_qty($raw);
                if ($raw !== '' && $level === null) {
                    throw new InvalidArgumentException('Enter a reorder level as a number, or leave it empty.');
                }
                Inventory::lockStock($pdo, $pid);
                $pdo->prepare('UPDATE products SET reorder_level = ? WHERE id = ?')->execute([$level, $pid]);
                inv_flash('success', $level === null ? 'Reorder warning removed.' : 'Reorder level set to ' . inv_fmt($level) . '.');
                break;

            case 'cost':
                $cost = inv_money((string) ($_POST['cost'] ?? ''));
                if ($cost === null) {
                    throw new InvalidArgumentException('Enter a unit cost, for example 45 or 45.50.');
                }
                Inventory::lockStock($pdo, $pid);
                $pdo->prepare('UPDATE products SET cost_price = ? WHERE id = ?')->execute([$cost, $pid]);
                inv_flash('success', 'Unit cost updated.');
                break;

            default:
                throw new InvalidArgumentException('Choose an action.');
        }

        $pdo->commit();
    } catch (InvalidArgumentException | RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        inv_flash('error', $e->getMessage());
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Inventory action failed: ' . $e->getMessage());
        inv_flash('error', 'Could not save. Please try again.');
    }

    inv_go($back);
}

/* ---------- Filters ---------- */

$q      = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 80));
$cat    = trim(mb_substr((string) ($_GET['cat'] ?? ''), 0, 60));
$status = in_array($_GET['status'] ?? '', ['low', 'out', 'untracked', 'tracked'], true) ? (string) $_GET['status'] : 'all';
$pick   = (int) ($_GET['pick'] ?? 0);      // preselect a product in the stock form
$mProd  = (int) ($_GET['product'] ?? 0);   // filter the movement history

$where  = [];
$params = [];
if ($q !== '') {
    $where[]  = '(p.name LIKE ? OR p.category LIKE ?)';
    $like     = '%' . addcslashes($q, '%_\\') . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($cat !== '') {
    $where[]  = 'p.category = ?';
    $params[] = $cat;
}
$statusSql = [
    'low'       => 'p.stock_qty IS NOT NULL AND p.stock_qty > 0 AND p.reorder_level IS NOT NULL AND p.stock_qty <= p.reorder_level',
    'out'       => 'p.stock_qty IS NOT NULL AND p.stock_qty <= 0',
    'untracked' => 'p.stock_qty IS NULL',
    'tracked'   => 'p.stock_qty IS NOT NULL',
];
if (isset($statusSql[$status])) {
    $where[] = $statusSql[$status];
}
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------- Data (guarded so a missing migration shows a clear message) ---------- */

$dbError = null;
$rows = $products = $moves = [];
$stats = ['total' => 0, 'tracked' => 0, 'low' => 0, 'out' => 0, 'value' => 0.0];

try {
    $stats = $pdo->query(
        "SELECT COUNT(*) AS total,
                COUNT(stock_qty) AS tracked,
                SUM(stock_qty IS NOT NULL AND stock_qty <= 0) AS `out`,
                SUM(stock_qty IS NOT NULL AND stock_qty > 0 AND reorder_level IS NOT NULL AND stock_qty <= reorder_level) AS low,
                COALESCE(SUM(CASE WHEN stock_qty > 0 THEN stock_qty * COALESCE(cost_price, 0) END), 0) AS value
         FROM products"
    )->fetch();

    $list = $pdo->prepare(
        "SELECT p.id, p.name, p.category, p.active, p.stock_qty, p.reorder_level, p.cost_price,
                (SELECT MAX(m.created_at) FROM inventory_movements m WHERE m.product_id = p.id) AS last_move
         FROM products p $W
         ORDER BY (p.stock_qty IS NULL), p.stock_qty ASC, p.name
         LIMIT 200"
    );
    $list->execute($params);
    $rows = $list->fetchAll();

    $products = $pdo->query('SELECT id, name, category, stock_qty FROM products ORDER BY name')->fetchAll();

    $mSql = "SELECT m.id, m.type, m.qty_change, m.qty_after, m.note, m.created_at,
                    p.name AS product, u.name AS staff, m.order_id
             FROM inventory_movements m
             LEFT JOIN products p ON p.id = m.product_id
             LEFT JOIN users u    ON u.id = m.user_id"
          . ($mProd > 0 ? ' WHERE m.product_id = ?' : '')
          . ' ORDER BY m.id DESC LIMIT 30';
    $ms = $pdo->prepare($mSql);
    $ms->execute($mProd > 0 ? [$mProd] : []);
    $moves = $ms->fetchAll();
} catch (PDOException $e) {
    error_log('Inventory page query failed: ' . $e->getMessage());
    $dbError = 'Inventory tables are missing. Run the inventory migration first.';
}

$typeLabel = [
    'receive' => 'Received',
    'sale'    => 'Sold',
    'waste'   => 'Waste',
    'count'   => 'Stock count',
    'track'   => 'Tracking on',
];

$categories = [];
foreach ($products as $p) {
    $categories[$p['category']] = true;
}
$categories = array_keys($categories);
sort($categories);

$selfUrl = $base . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');

/* ---------- Layout ---------- */

$pageTitle  = 'Inventory';
$activePage = 'inventory';
$crumbs     = ['Admin' => BASE_URL . 'admin', 'Inventory' => null];

require APP_ROOT . '/app/shared/views/admin/header.php';
?>

<style>
.iv-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.iv-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;flex-direction:column;gap:6px;min-width:0}
.iv-stat-label{font-size:12.5px;color:var(--text-3);font-weight:500}
.iv-stat-value{font-size:24px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.iv-stat-value.is-bad{color:var(--danger)}
.iv-stat-value.is-warn{color:var(--warning)}
.iv-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.6fr);gap:16px;align-items:start}
.iv-card-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
.iv-title{font-size:17px;margin-top:4px}
.iv-muted{color:var(--text-3);font-size:12.5px}
.iv-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.iv-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.iv-input,.iv-select{width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;outline:none}
.iv-input:focus,.iv-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.iv-form{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.iv-form .iv-wide{grid-column:1/-1}
.iv-hint{font-size:12px;color:var(--text-3);line-height:1.5}
.iv-inline{display:flex;gap:6px;align-items:center}
.iv-inline .iv-input{height:32px;width:96px;padding:0 8px;font-size:13px}
.iv-btn-sm{min-height:32px;padding:5px 10px;font-size:12.5px}
.iv-filters{display:grid;grid-template-columns:2fr 1.2fr 1.2fr auto;gap:12px;align-items:end}
.iv-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.iv-name{color:var(--text);font-weight:600}
.iv-qty{font-weight:700;font-variant-numeric:tabular-nums}
.iv-qty.is-neg{color:var(--danger)}
.iv-plus{color:var(--success)}
.iv-minus{color:var(--danger)}
.iv-tag{display:inline-block;padding:2px 9px;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:600}
.iv-dberr{padding:12px 16px;border-radius:var(--radius);background:var(--warning-soft);color:var(--warning);border:1px solid var(--warning);font-weight:500}

@media (max-width:1280px){
  .iv-grid{grid-template-columns:1fr}
}
@media (max-width:1024px){
  .iv-filters{grid-template-columns:1fr 1fr}
  .iv-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:560px){
  .iv-filters,.iv-form{grid-template-columns:1fr}
  .iv-stats{gap:10px}
  .iv-stat{padding:12px}
  .iv-stat-value{font-size:19px}
  .iv-inline{flex-wrap:wrap}
  .iv-inline .iv-input{width:100%}
}
</style>

<!-- Page head -->
<div class="page-head">
  <div>
    <p class="eyebrow">Catalogue</p>
    <h1 class="page-title">Inventory</h1>
    <p class="page-sub">Stock is taken off automatically when an order is paid. Use this page for deliveries, waste and counts.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn--ghost" href="<?= h(legacy_url('admin', 'products')) ?>">Products</a>
    <a class="btn btn--primary" href="#stock-form">+ Record stock</a>
  </div>
</div>

<?php if ($dbError): ?>
  <div class="iv-dberr" role="alert"><?= h($dbError) ?></div>
<?php endif; ?>

<!-- Summary -->
<section class="iv-stats" aria-label="Inventory summary">
  <div class="iv-stat">
    <span class="iv-stat-label">Products</span>
    <span class="iv-stat-value"><?= (int) $stats['total'] ?></span>
  </div>
  <div class="iv-stat">
    <span class="iv-stat-label">Tracked</span>
    <span class="iv-stat-value"><?= (int) $stats['tracked'] ?></span>
  </div>
  <div class="iv-stat">
    <span class="iv-stat-label">Low or out</span>
    <span class="iv-stat-value <?= ((int) $stats['low'] + (int) $stats['out']) > 0 ? 'is-warn' : '' ?>"><?= (int) $stats['low'] + (int) $stats['out'] ?></span>
  </div>
  <div class="iv-stat">
    <span class="iv-stat-label">Stock value (at cost)</span>
    <span class="iv-stat-value"><?= h(money($stats['value'])) ?></span>
  </div>
</section>

<div class="iv-grid">

  <!-- Record stock -->
  <section class="card" id="stock-form" aria-labelledby="rec-title">
    <div>
      <p class="eyebrow">Record</p>
      <h2 class="iv-title" id="rec-title">Stock movement</h2>
    </div>

    <form method="post" action="<?= h($selfUrl) ?>" class="iv-form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="return" value="<?= h($selfUrl) ?>">

      <div class="iv-field iv-wide">
        <label for="fAction">What happened</label>
        <select class="iv-select" id="fAction" name="action">
          <option value="receive">Received delivery</option>
          <option value="waste">Waste or spoilage</option>
          <option value="count">Stock count (set the shelf total)</option>
          <option value="cost">Set unit cost</option>
        </select>
      </div>

      <div class="iv-field iv-wide">
        <label for="fProd">Product</label>
        <select class="iv-select" id="fProd" name="product_id" required>
          <option value="">Choose a product…</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $pick === (int) $p['id'] ? 'selected' : '' ?>>
              <?= h($p['name']) ?> · <?= h($p['category']) ?><?= $p['stock_qty'] === null ? ' (not tracked)' : ' (' . h(inv_fmt((float) $p['stock_qty'])) . ' in stock)' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="iv-field">
        <label for="fQty">Quantity</label>
        <input class="iv-input num" id="fQty" name="qty" inputmode="decimal" placeholder="e.g. 24">
      </div>

      <div class="iv-field">
        <label for="fCost">Unit cost (optional)</label>
        <input class="iv-input num" id="fCost" name="cost" inputmode="decimal" placeholder="e.g. 45.50">
      </div>

      <div class="iv-field iv-wide">
        <label for="fNote">Note</label>
        <input class="iv-input" id="fNote" name="note" maxlength="255" placeholder="Supplier, reason, or reference">
      </div>

      <div class="iv-wide iv-actions">
        <button class="btn btn--primary" type="submit">Record</button>
        <span class="iv-hint">Receive adds stock. Waste needs a note. Count sets the total on the shelf. Cost only changes the unit cost.</span>
      </div>
    </form>
  </section>

  <!-- Stock list -->
  <section class="card" aria-labelledby="list-title">
    <div class="iv-card-head">
      <div>
        <p class="eyebrow">Stock</p>
        <h2 class="iv-title" id="list-title">Stock levels</h2>
      </div>
      <span class="iv-muted"><?= count($rows) ?> shown · low and out first</span>
    </div>

    <form method="get" action="<?= h($base) ?>" class="iv-filters">
      <div class="iv-field">
        <label for="qq">Search</label>
        <input class="iv-input" id="qq" type="search" name="q" value="<?= h($q) ?>" placeholder="Name or category">
      </div>
      <div class="iv-field">
        <label for="qc">Category</label>
        <select class="iv-select" id="qc" name="cat">
          <option value="">All</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="iv-field">
        <label for="qs">Show</label>
        <select class="iv-select" id="qs" name="status">
          <option value="all"       <?= $status === 'all' ? 'selected' : '' ?>>All products</option>
          <option value="low"       <?= $status === 'low' ? 'selected' : '' ?>>Low stock</option>
          <option value="out"       <?= $status === 'out' ? 'selected' : '' ?>>Out of stock</option>
          <option value="tracked"   <?= $status === 'tracked' ? 'selected' : '' ?>>Tracked</option>
          <option value="untracked" <?= $status === 'untracked' ? 'selected' : '' ?>>Not tracked</option>
        </select>
      </div>
      <div class="iv-actions">
        <button class="btn btn--ghost" type="submit">Apply</button>
        <?php if ($q !== '' || $cat !== '' || $status !== 'all'): ?>
          <a class="btn btn--ghost" href="<?= h($base) ?>">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Product</th>
            <th class="r">On hand</th>
            <th class="r">Reorder at</th>
            <th class="r">Unit cost</th>
            <th class="r">Value</th>
            <th>Status</th>
            <th>Last change</th>
            <th class="r">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="8" class="empty">No products match these filters.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $r):
              $qty     = $r['stock_qty'] === null ? null : (float) $r['stock_qty'];
              $reorder = $r['reorder_level'] === null ? null : (float) $r['reorder_level'];
              $cost    = $r['cost_price'] === null ? null : (float) $r['cost_price'];
              [$stLabel, $stClass] = inv_status($qty, $reorder);
              $value   = ($qty !== null && $qty > 0 && $cost !== null) ? $qty * $cost : null;
          ?>
            <tr>
              <td>
                <div class="iv-name"><?= h($r['name']) ?></div>
                <span class="iv-tag"><?= h($r['category']) ?></span>
              </td>
              <td class="r iv-qty<?= $qty !== null && $qty < 0 ? ' is-neg' : '' ?>"><?= h(inv_fmt($qty)) ?></td>
              <td class="r">
                <form method="post" action="<?= h($selfUrl) ?>" class="iv-inline" style="justify-content:flex-end">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                  <input type="hidden" name="action" value="reorder">
                  <input type="hidden" name="product_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="return" value="<?= h($selfUrl) ?>">
                  <input class="iv-input num" name="level" inputmode="decimal" value="<?= h($reorder !== null ? inv_fmt($reorder) : '') ?>" placeholder="—" aria-label="Reorder level for <?= h($r['name']) ?>">
                  <button class="btn btn--ghost iv-btn-sm" type="submit">Set</button>
                </form>
              </td>
              <td class="r">
                <form method="post" action="<?= h($selfUrl) ?>" class="iv-inline" style="justify-content:flex-end">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                  <input type="hidden" name="action" value="cost">
                  <input type="hidden" name="product_id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="return" value="<?= h($selfUrl) ?>">
                  <input class="iv-input num" name="cost" inputmode="decimal" value="<?= h($cost !== null ? number_format($cost, 2, '.', '') : '') ?>" placeholder="—" aria-label="Unit cost for <?= h($r['name']) ?>">
                  <button class="btn btn--ghost iv-btn-sm" type="submit">Set</button>
                </form>
              </td>
              <td class="r num"><?= $value !== null ? h(money($value)) : '—' ?></td>
              <td><span class="badge <?= h($stClass) ?>"><?= h($stLabel) ?></span></td>
              <td class="iv-muted"><?= $r['last_move'] ? h(date('j M, H:i', strtotime($r['last_move']))) : '—' ?></td>
              <td class="r">
                <div class="iv-actions" style="justify-content:flex-end">
                  <?php if ($qty === null): ?>
                    <form method="post" action="<?= h($selfUrl) ?>">
                      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                      <input type="hidden" name="action" value="track">
                      <input type="hidden" name="product_id" value="<?= (int) $r['id'] ?>">
                      <input type="hidden" name="return" value="<?= h($selfUrl) ?>">
                      <button class="btn btn--ghost iv-btn-sm" type="submit">Start tracking</button>
                    </form>
                  <?php else: ?>
                    <a class="btn btn--ghost iv-btn-sm" href="<?= h($base . '?pick=' . (int) $r['id'] . '#stock-form') ?>">Adjust</a>
                    <a class="btn btn--ghost iv-btn-sm" href="<?= h($base . '?product=' . (int) $r['id']) ?>">History</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="iv-hint">Products that are not tracked do not reduce stock when sold. Start tracking to include them. Stock can go negative if a sale is recorded before a delivery. A count fixes it.</p>
  </section>
</div>

<!-- Movement history -->
<section class="card" aria-labelledby="hist-title">
  <div class="iv-card-head">
    <div>
      <p class="eyebrow">History</p>
      <h2 class="iv-title" id="hist-title"><?= $mProd > 0 ? 'Movements for one product' : 'Latest movements' ?></h2>
    </div>
    <?php if ($mProd > 0): ?>
      <a class="btn btn--ghost iv-btn-sm" href="<?= h($base) ?>">Show all</a>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>When</th><th>Product</th><th>Type</th><th class="r">Change</th><th class="r">Balance</th><th>Staff</th><th>Note</th></tr>
      </thead>
      <tbody>
        <?php if (!$moves): ?>
          <tr><td colspan="7" class="empty">No stock movements yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($moves as $m):
            $chg = (float) $m['qty_change'];
            $label = $typeLabel[$m['type']] ?? ucfirst((string) $m['type']);
            $note  = (string) ($m['note'] ?? '');
            if ($m['type'] === 'sale' && !empty($m['order_id'])) {
                $note = 'Order #' . (int) $m['order_id'];
            }
        ?>
          <tr>
            <td class="iv-muted"><?= h(date('j M, H:i', strtotime($m['created_at']))) ?></td>
            <td class="iv-name"><?= h($m['product'] ?? '—') ?></td>
            <td><?= h($label) ?></td>
            <td class="r iv-qty <?= $chg >= 0 ? 'iv-plus' : 'iv-minus' ?>"><?= ($chg > 0 ? '+' : '') . h(inv_fmt($chg)) ?></td>
            <td class="r num"><?= h(inv_fmt((float) $m['qty_after'])) ?></td>
            <td><?= h($m['staff'] ?? 'System') ?></td>
            <td class="iv-muted" style="white-space:normal;min-width:180px"><?= h($note) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>