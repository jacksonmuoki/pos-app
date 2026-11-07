<?php
/* Admin: categories. Categories are stored in their own table, so a category can exist
   before any product uses it. Renaming updates every product in it. Handles its own POST actions. */
declare(strict_types=1);
$pdo  = db();
$base = BASE_URL . 'admin?page=categories';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/* ---------- Helpers ---------- */

function adm_cat_flash(string $type, string $msg): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
}

function adm_cat_go(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function adm_cat_text(string $s, int $max): string
{
    return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $s)), 0, $max);
}

/** True when another category (or a product category) already uses this name, ignoring case. */
function adm_cat_exists(PDO $pdo, string $name, string $except = ''): bool
{
    $s = $pdo->prepare(
        "SELECT 1 FROM (
             SELECT name FROM categories
             UNION
             SELECT category AS name FROM products
         ) c
         WHERE LOWER(name) = LOWER(?) AND name <> ?
         LIMIT 1"
    );
    $s->execute([$name, $except]);
    return (bool) $s->fetchColumn();
}

/* ---------- POST actions ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        adm_cat_flash('error', 'Your session expired. Please try again.');
        adm_cat_go($base);
    }

    $action = (string) ($_POST['action'] ?? '');
    $old    = adm_cat_text((string) ($_POST['old'] ?? ''), 60);

    try {
        switch ($action) {
            case 'add':
                $name = adm_cat_text((string) ($_POST['name'] ?? ''), 60);
                if ($name === '') {
                    adm_cat_flash('error', 'Enter a category name.');
                    adm_cat_go($base);
                }
                if (adm_cat_exists($pdo, $name)) {
                    adm_cat_flash('error', '“' . $name . '” already exists.');
                    adm_cat_go($base);
                }
                $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]);
                adm_cat_flash('success', '“' . $name . '” added. Add products to it from the products page.');
                break;

            case 'rename':
                $new = adm_cat_text((string) ($_POST['new_name'] ?? ''), 60);
                if ($old === '' || $new === '') {
                    adm_cat_flash('error', 'Enter a new name for this category.');
                    adm_cat_go($base);
                }
                if ($old === $new) {
                    adm_cat_flash('info', 'The name is unchanged.');
                    adm_cat_go($base);
                }
                if (adm_cat_exists($pdo, $new, $old)) {
                    adm_cat_flash('error', 'A category called “' . $new . '” already exists.');
                    adm_cat_go($base);
                }
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE categories SET name = ? WHERE name = ?')->execute([$new, $old]);
                $u = $pdo->prepare('UPDATE products SET category = ? WHERE category = ?');
                $u->execute([$new, $old]);
                $pdo->commit();
                adm_cat_flash('success', 'Renamed to “' . $new . '”. ' . $u->rowCount() . ' product(s) updated.');
                break;

            case 'hide':
            case 'show':
                $on = $action === 'show' ? 1 : 0;
                $pdo->prepare('UPDATE products SET active = ? WHERE category = ?')->execute([$on, $old]);
                adm_cat_flash('success', '“' . $old . '” ' . ($on ? 'is now shown in the till.' : 'is hidden from the till.'));
                break;

            case 'delete':
                $used = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category = ?');
                $used->execute([$old]);
                if ((int) $used->fetchColumn() > 0) {
                    adm_cat_flash('error', '“' . $old . '” still has products. Move or delete them first.');
                    adm_cat_go($base);
                }
                $pdo->prepare('DELETE FROM categories WHERE name = ?')->execute([$old]);
                adm_cat_flash('success', '“' . $old . '” deleted.');
                break;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Categories admin error: ' . $e->getMessage());
        adm_cat_flash('error', 'Could not save. Please try again.');
    }

    adm_cat_go($base);
}

/* ---------- Data ---------- */

// Product counts per category
$agg = $pdo->query(
    "SELECT category,
            COUNT(*) AS items,
            COALESCE(SUM(active), 0) AS shown,
            COALESCE(AVG(price), 0) AS avg_price
     FROM products
     GROUP BY category"
)->fetchAll(PDO::FETCH_UNIQUE);

// Every category: those saved on their own, plus any used by products
$names = $pdo->query(
    "SELECT name FROM categories
     UNION
     SELECT DISTINCT category AS name FROM products"
)->fetchAll(PDO::FETCH_COLUMN);
sort($names, SORT_NATURAL | SORT_FLAG_CASE);

$rows = [];
foreach ($names as $n) {
    $a = $agg[$n] ?? null;
    $rows[] = [
        'category'  => (string) $n,
        'items'     => (int) ($a['items'] ?? 0),
        'shown'     => (int) ($a['shown'] ?? 0),
        'avg_price' => (float) ($a['avg_price'] ?? 0),
    ];
}

// Sales for the last 30 days, by category
$since = date('Y-m-d H:i:s', strtotime('-30 days'));
$sales = [];
$s = $pdo->prepare(
    "SELECT p.category, COALESCE(SUM(oi.qty), 0) AS qty, COALESCE(SUM(oi.qty * oi.price), 0) AS rev
     FROM order_items oi
     JOIN orders o   ON o.id = oi.order_id
     JOIN products p ON p.id = oi.product_id
     WHERE o.status = 'paid' AND o.paid_at >= ?
     GROUP BY p.category"
);
$s->execute([$since]);
foreach ($s->fetchAll() as $r) {
    $sales[$r['category']] = $r;
}

$totalCats  = count($rows);
$totalItems = array_sum(array_column($rows, 'items'));
$totalShown = array_sum(array_column($rows, 'shown'));

/* ---------- Layout ---------- */

$pageTitle  = 'Categories';
$activePage = 'categories';
$crumbs     = ['Admin' => BASE_URL . 'admin', 'Products' => BASE_URL . 'admin?page=products', 'Categories' => null];

require APP_ROOT . '/app/shared/views/admin/header.php';
?>

<style>
.cg-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.cg-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;flex-direction:column;gap:6px}
.cg-stat-label{font-size:12.5px;color:var(--text-3);font-weight:500}
.cg-stat-value{font-size:24px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.cg-add{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end}
.cg-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.cg-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.cg-input{width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;outline:none}
.cg-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.cg-inline{display:flex;gap:6px;align-items:center;min-width:220px}
.cg-inline .cg-input{height:34px;font-size:13px;padding:0 10px}
.cg-actions{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap}
.cg-inline-form{display:inline}
.cg-btn-sm{min-height:32px;padding:5px 10px;font-size:12.5px}
.cg-tag{display:inline-block;padding:3px 10px;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-size:12.5px;font-weight:600}
.cg-muted{color:var(--text-3);font-size:12.5px}
.cg-strong{color:var(--text);font-weight:600}
.cg-sub{font-size:17px;margin-top:4px}
.cg-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}

@media (max-width:1024px){
  .cg-inline{min-width:0}
}
@media (max-width:560px){
  .cg-stats{grid-template-columns:1fr 1fr 1fr;gap:8px}
  .cg-stat{padding:12px}
  .cg-stat-value{font-size:18px}
  .cg-add{grid-template-columns:1fr}
  .cg-inline{flex-direction:column;align-items:stretch}
}
</style>

<!-- Page head -->
<div class="page-head">
  <div>
    <p class="eyebrow">Catalogue</p>
    <h1 class="page-title">Categories</h1>
    <p class="page-sub">Group products for the till. Renaming applies to every product in the category.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn--ghost" href="<?= h(BASE_URL . 'admin?page=products') ?>">All products</a>
    <a class="btn btn--primary" href="<?= h(BASE_URL . 'admin?page=products&new=1') ?>">+ New product</a>
  </div>
</div>

<!-- Summary -->
<section class="cg-stats" aria-label="Category summary">
  <div class="cg-stat"><span class="cg-stat-label">Categories</span><span class="cg-stat-value"><?= (int) $totalCats ?></span></div>
  <div class="cg-stat"><span class="cg-stat-label">Products</span><span class="cg-stat-value"><?= (int) $totalItems ?></span></div>
  <div class="cg-stat"><span class="cg-stat-label">Shown in till</span><span class="cg-stat-value"><?= (int) $totalShown ?></span></div>
</section>

<!-- Add -->
<section class="card" aria-labelledby="add-title">
  <div>
    <p class="eyebrow">New</p>
    <h2 id="add-title" class="cg-sub">Add a category</h2>
    <p class="cg-muted" style="margin-top:4px">The category is saved straight away. Add products to it from the products page.</p>
  </div>
  <form method="post" action="<?= h($base) ?>" class="cg-add" style="margin-top:14px">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="action" value="add">
    <div class="cg-field">
      <label for="newCat">Category name</label>
      <input class="cg-input" id="newCat" name="name" maxlength="60" required placeholder="e.g. Beverages">
    </div>
    <button class="btn btn--primary" type="submit">Add category</button>
  </form>
</section>

<!-- List -->
<section class="card" aria-labelledby="list-title">
  <div class="cg-head">
    <div>
      <p class="eyebrow">Menu</p>
      <h2 id="list-title" class="cg-sub">All categories</h2>
    </div>
    <span class="cg-muted">Sales figures cover the last 30 days</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Category</th>
          <th class="r">Products</th>
          <th class="r">In till</th>
          <th class="r">Avg price</th>
          <th class="r">Sold (30d)</th>
          <th class="r">Revenue (30d)</th>
          <th>Status</th>
          <th>Rename</th>
          <th class="r">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="9" class="empty">No categories yet. Add one above.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r):
            $name  = $r['category'];
            $shown = $r['shown'];
            $items = $r['items'];
            $sale  = $sales[$name] ?? ['qty' => 0, 'rev' => 0];

            if ($items === 0)        { $statusHtml = '<span class="badge badge--muted">No products yet</span>'; }
            elseif ($shown === 0)    { $statusHtml = '<span class="badge badge--muted">Hidden</span>'; }
            elseif ($shown < $items) { $statusHtml = '<span class="badge badge--warn">' . $shown . ' of ' . $items . ' shown</span>'; }
            else                     { $statusHtml = '<span class="badge badge--ok">Shown</span>'; }
        ?>
          <tr>
            <td><span class="cg-tag"><?= h($name) ?></span></td>
            <td class="r num cg-strong"><?= $items ?></td>
            <td class="r num"><?= $shown ?></td>
            <td class="r num"><?= $items > 0 ? h(money($r['avg_price'])) : '–' ?></td>
            <td class="r num"><?= (int) $sale['qty'] ?></td>
            <td class="r num cg-strong"><?= h(money($sale['rev'])) ?></td>
            <td><?= $statusHtml ?></td>
            <td>
              <form method="post" action="<?= h($base) ?>" class="cg-inline cg-inline-form">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="old" value="<?= h($name) ?>">
                <input class="cg-input" name="new_name" maxlength="60" value="<?= h($name) ?>" aria-label="New name for <?= h($name) ?>">
                <button class="btn btn--ghost cg-btn-sm" type="submit">Save</button>
              </form>
            </td>
            <td class="r">
              <div class="cg-actions">
                <?php if ($items > 0): ?>
                  <a class="btn btn--ghost cg-btn-sm" href="<?= h(BASE_URL . 'admin?page=products&cat=' . rawurlencode($name)) ?>">View products</a>
                  <form method="post" action="<?= h($base) ?>" class="cg-inline-form">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="old" value="<?= h($name) ?>">
                    <input type="hidden" name="action" value="<?= $shown > 0 ? 'hide' : 'show' ?>">
                    <button class="btn btn--ghost cg-btn-sm" type="submit"><?= $shown > 0 ? 'Hide from till' : 'Show in till' ?></button>
                  </form>
                <?php else: ?>
                  <a class="btn btn--ghost cg-btn-sm" href="<?= h(BASE_URL . 'admin?page=products&new=1&category=' . rawurlencode($name)) ?>">Add product</a>
                  <form method="post" action="<?= h($base) ?>" class="cg-inline-form"
                        data-confirm="Delete the category “<?= h($name) ?>”?">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="old" value="<?= h($name) ?>">
                    <button class="btn btn--danger cg-btn-sm" type="submit">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="cg-muted">Hiding a category hides all its products from the till. Their sales history is kept. A category can only be deleted when it has no products.</p>
</section>

<script>
(function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
})();
</script>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>