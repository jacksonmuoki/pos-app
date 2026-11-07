<?php
/* Admin: products. Add and edit in an in-page dialog, show or hide, and delete.
   The list keeps its filters and page. Categories are chosen from the saved list only. */

$pdo  = db();
$base = BASE_URL . 'admin?page=products';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/* ---------- Helpers ---------- */

if (!function_exists('pr_flash')) {
    function pr_flash(string $type, string $msg): void
    {
        $_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
    }
}

if (!function_exists('pr_go')) {
    function pr_go(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('pr_text')) {
    function pr_text(string $s, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $s)), 0, $max);
    }
}

/** Accepts 150, 99.5, 1,200.00. Returns null when the value is not a valid price. */
if (!function_exists('pr_price')) {
    function pr_price(string $s): ?float
    {
        $s = str_replace([',', ' '], '', trim($s));
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $s)) {
            return null;
        }
        return round((float) $s, 2);
    }
}

/** Returns an error message, or null when the product is valid. */
if (!function_exists('pr_validate')) {
    function pr_validate(PDO $pdo, string $name, string $cat, ?float $price, int $id): ?string
    {
        if ($name === '') {
            return 'Enter a product name.';
        }
        if ($cat === '') {
            return 'Choose a category.';
        }
        if ($price === null || $price <= 0) {
            return 'Enter a price greater than zero, for example 150 or 99.50.';
        }
        $s = $pdo->prepare('SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
        $s->execute([$name, $id]);
        if ($s->fetch()) {
            return 'A product called "' . $name . '" already exists.';
        }
        return null;
    }
}

/* ---------- Valid categories (the only ones a product may use) ---------- */

$validCats = $pdo->query(
    "SELECT name FROM categories
     UNION
     SELECT DISTINCT category AS name FROM products
     ORDER BY name"
)->fetchAll(PDO::FETCH_COLUMN);

/* ---------- POST actions ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        pr_flash('error', 'Your session expired. Please try again.');
        pr_go($base);
    }

    // Return to the same filters and page the user was on.
    $back = (string) ($_POST['return'] ?? $base);
    if (!str_starts_with($back, $base)) {
        $back = $base;
    }

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        switch ($action) {

            case 'save':
                $name   = pr_text((string) ($_POST['name'] ?? ''), 100);
                $cat    = pr_text((string) ($_POST['category'] ?? ''), 60);
                $price  = pr_price((string) ($_POST['price'] ?? ''));
                $active = !empty($_POST['active']) ? 1 : 0;

                // Only a category from the saved list is allowed.
                if ($cat !== '' && !in_array($cat, $validCats, true)) {
                    $err = 'Choose a category from the list.';
                } else {
                    $err = pr_validate($pdo, $name, $cat, $price, $id);
                }

                if ($err !== null) {
                    // Keep what was typed and reopen the dialog on the same list.
                    $_SESSION['prod_old'] = [
                        'id'       => $id,
                        'name'     => $name,
                        'category' => $cat,
                        'price'    => (string) ($_POST['price'] ?? ''),
                        'active'   => (string) $active,
                    ];
                    pr_flash('error', $err);
                    pr_go($back);
                }

                if ($id > 0) {
                    $pdo->prepare('UPDATE products SET name = ?, category = ?, price = ?, active = ? WHERE id = ?')
                        ->execute([$name, $cat, $price, $active, $id]);
                    pr_flash('success', '"' . $name . '" updated.');
                } else {
                    $pdo->prepare('INSERT INTO products (name, category, price, active) VALUES (?, ?, ?, ?)')
                        ->execute([$name, $cat, $price, $active]);
                    pr_flash('success', '"' . $name . '" added to the menu.');
                }
                pr_go($back);

            case 'toggle':
                $pdo->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([$id]);
                pr_flash('success', 'Product visibility changed.');
                break;

            case 'delete':
                $used = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
                $used->execute([$id]);
                if ((int) $used->fetchColumn() > 0) {
                    // Keep sales history intact: hide the product instead of deleting it.
                    $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([$id]);
                    pr_flash('warning', 'This product has sales history, so it was hidden instead of deleted.');
                } else {
                    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
                    pr_flash('success', 'Product deleted.');
                }
                break;
        }
    } catch (PDOException $e) {
        error_log('Products admin error: ' . $e->getMessage());
        pr_flash('error', 'Could not save. Please try again.');
    }

    pr_go($back);
}

/* ---------- Filters ---------- */

$q      = pr_text((string) ($_GET['q'] ?? ''), 80);
$cat    = pr_text((string) ($_GET['cat'] ?? ''), 60);
$status = in_array($_GET['status'] ?? '', ['active', 'hidden'], true) ? (string) $_GET['status'] : 'all';
$page   = max(1, (int) ($_GET['page'] ?? 1));
$per    = 20;

$where  = [];
$params = [];
if ($q !== '') {
    $where[]  = '(name LIKE ? OR category LIKE ?)';
    $like     = '%' . addcslashes($q, '%_\\') . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($cat !== '') {
    $where[]  = 'category = ?';
    $params[] = $cat;
}
if ($status === 'active') $where[] = 'active = 1';
if ($status === 'hidden') $where[] = 'active = 0';
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM products $W");
$cnt->execute($params);
$total = (int) $cnt->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$list = $pdo->prepare("SELECT id, name, category, price, active FROM products $W ORDER BY category, name LIMIT $per OFFSET $off");
$list->execute($params);
$rows = $list->fetchAll();

/* ---------- Summary and lookups ---------- */

$stats = $pdo->query(
    "SELECT COUNT(*) AS n,
            COALESCE(SUM(active), 0) AS a,
            COUNT(DISTINCT category) AS c,
            COALESCE(AVG(CASE WHEN active = 1 THEN price END), 0) AS avg_price
     FROM products"
)->fetch();

// The filter dropdown and the product dialog both use the same list.
$categories = $validCats;

/* Values to refill the dialog after a failed save. */
$prefill = $_SESSION['prod_old'] ?? null;
unset($_SESSION['prod_old']);

/* Category preselected when arriving from Categories → Add product. Only valid names are used. */
$openNew   = ($_GET['new'] ?? '') === '1';
$presetCat = pr_text((string) ($_GET['category'] ?? ''), 60);
if (!in_array($presetCat, $validCats, true)) {
    $presetCat = '';
}

$pageLink = function (int $p) use ($base, $q, $cat, $status): string {
    $qs = http_build_query(array_filter([
        'q'      => $q,
        'cat'    => $cat,
        'status' => $status !== 'all' ? $status : '',
        'page'   => $p > 1 ? $p : '',
    ], fn($v) => $v !== ''));
    return $base . ($qs !== '' ? '&' . $qs : '');
};

$currentUrl = $pageLink($page);

/* ---------- Layout ---------- */

$pageTitle  = 'Products';
$activePage = 'products';
$crumbs     = ['Admin' => BASE_URL . 'admin', 'Products' => null];

require APP_ROOT . '/app/shared/views/admin/header.php';
?>

<style>
.pr-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.pr-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;flex-direction:column;gap:6px;min-width:0}
.pr-stat-label{font-size:12.5px;color:var(--text-3);font-weight:500}
.pr-stat-value{font-size:24px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.pr-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;gap:14px;min-width:0}
.pr-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
.pr-title{font-size:17px;margin-top:4px}
.pr-muted{font-size:12.5px;color:var(--text-3)}
.pr-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.pr-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.pr-input,.pr-select{width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;outline:none;transition:border-color .12s,box-shadow .12s}
.pr-input:focus,.pr-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.pr-select:disabled{opacity:.6;cursor:not-allowed}
.pr-check{display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--text-2);height:40px}
.pr-check input{width:18px;height:18px;accent-color:var(--primary)}
.pr-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pr-filters{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end}
.pr-tag{display:inline-block;padding:2px 9px;border-radius:999px;background:var(--primary-soft);color:var(--primary);font-size:12px;font-weight:600}
.pr-strong{color:var(--text);font-weight:600}
.pr-row-actions{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap}
.pr-inline{display:inline}
.pr-btn-sm{min-height:32px;padding:5px 10px;font-size:12.5px}
.pr-pager{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;color:var(--text-3);padding-top:8px}
.pr-pager nav{display:flex;gap:6px}
.pr-pager a,.pr-pager span{padding:6px 11px;border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-2)}
.pr-pager span.is-on{background:var(--primary-soft);color:var(--primary);border-color:var(--primary);font-weight:600}

/* Dialog */
.pr-overlay{position:fixed;inset:0;z-index:90;background:rgba(3,7,18,.6);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:16px}
.pr-overlay[hidden]{display:none}
.pr-dialog{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);width:100%;max-width:480px;max-height:calc(100dvh - 32px);overflow:auto;display:flex;flex-direction:column}
.pr-dhead{display:flex;justify-content:space-between;align-items:center;padding:16px 18px;border-bottom:1px solid var(--border)}
.pr-dhead h2{font-size:16px}
.pr-x{width:34px;height:34px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--surface);color:var(--text-2);font-size:18px;line-height:1}
.pr-x:hover{background:var(--surface-2);color:var(--text)}
.pr-dbody{padding:18px;display:flex;flex-direction:column;gap:14px}
.pr-dfoot{display:flex;justify-content:flex-end;gap:8px;padding:14px 18px;border-top:1px solid var(--border)}
.pr-hint{font-size:12px;color:var(--text-3);line-height:1.5}
.pr-warn{font-size:13px;color:var(--text-2);background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px}

@media (max-width:1024px){
  .pr-filters{grid-template-columns:1fr 1fr}
  .pr-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:560px){
  .pr-filters{grid-template-columns:1fr}
  .pr-stat-value{font-size:20px}
  .pr-pager{justify-content:center;text-align:center}
  .pr-dfoot .btn{flex:1}
}
</style>

<!-- Page head -->
<div class="page-head">
  <div>
    <p class="eyebrow">Catalogue</p>
    <h1 class="page-title">Products</h1>
    <p class="page-sub">Items the cashier can sell. Hidden products stay in your records but do not appear in the till.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn--ghost" href="<?= h(BASE_URL . 'admin?page=categories') ?>">Manage categories</a>
    <button class="btn btn--primary" type="button" data-pm-new>+ New product</button>
  </div>
</div>

<!-- Summary -->
<section class="pr-stats" aria-label="Product summary">
  <div class="pr-stat"><span class="pr-stat-label">Total products</span><span class="pr-stat-value"><?= (int) $stats['n'] ?></span></div>
  <div class="pr-stat"><span class="pr-stat-label">Shown in till</span><span class="pr-stat-value"><?= (int) $stats['a'] ?></span></div>
  <div class="pr-stat"><span class="pr-stat-label">Categories</span><span class="pr-stat-value"><?= count($validCats) ?></span></div>
  <div class="pr-stat"><span class="pr-stat-label">Average price (shown)</span><span class="pr-stat-value"><?= h(money($stats['avg_price'])) ?></span></div>
</section>

<!-- Filters and list -->
<section class="pr-card" aria-labelledby="list-title">
  <div class="pr-head">
    <div>
      <p class="eyebrow">Menu</p>
      <h2 class="pr-title" id="list-title">All products</h2>
    </div>
    <span class="pr-muted"><?= (int) $total ?> result(s)</span>
  </div>

  <form method="get" action="<?= h($base) ?>" class="pr-filters">
    <div class="pr-field">
      <label for="fq">Search</label>
      <input class="pr-input" id="fq" type="search" name="q" value="<?= h($q) ?>" placeholder="Name or category">
    </div>
    <div class="pr-field">
      <label for="fcat">Category</label>
      <select class="pr-select" id="fcat" name="cat">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="pr-field">
      <label for="fst">Status</label>
      <select class="pr-select" id="fst" name="status">
        <option value="all"    <?= $status === 'all' ? 'selected' : '' ?>>All</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Shown in till</option>
        <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Hidden</option>
      </select>
    </div>
    <div class="pr-actions">
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
          <th>Category</th>
          <th class="r">Price</th>
          <th>Status</th>
          <th class="r">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="empty">No products match these filters.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="pr-strong"><?= h($r['name']) ?></td>
            <td><span class="pr-tag"><?= h($r['category']) ?></span></td>
            <td class="r num pr-strong"><?= h(money($r['price'])) ?></td>
            <td>
              <?= (int) $r['active']
                    ? '<span class="badge badge--ok">Shown</span>'
                    : '<span class="badge badge--muted">Hidden</span>' ?>
            </td>
            <td class="r">
              <div class="pr-row-actions">
                <button class="btn btn--ghost pr-btn-sm" type="button"
                        data-pm-edit
                        data-id="<?= (int) $r['id'] ?>"
                        data-name="<?= h($r['name']) ?>"
                        data-category="<?= h($r['category']) ?>"
                        data-price="<?= h(number_format((float) $r['price'], 2, '.', '')) ?>"
                        data-active="<?= (int) $r['active'] ?>">Edit</button>

                <form method="post" action="<?= h($base) ?>" class="pr-inline">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="return" value="<?= h($currentUrl) ?>">
                  <button class="btn btn--ghost pr-btn-sm" type="submit"><?= (int) $r['active'] ? 'Hide' : 'Show' ?></button>
                </form>

                <form method="post" action="<?= h($base) ?>" class="pr-inline"
                      data-confirm="Delete “<?= h($r['name']) ?>”? If it has sales history it will be hidden instead.">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="return" value="<?= h($currentUrl) ?>">
                  <button class="btn btn--danger pr-btn-sm" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pr-pager">
      <span>Page <?= (int) $page ?> of <?= (int) $pages ?></span>
      <nav aria-label="Pages">
        <?php if ($page > 1): ?><a href="<?= h($pageLink($page - 1)) ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages): ?><a href="<?= h($pageLink($page + 1)) ?>">Next</a><?php endif; ?>
      </nav>
    </div>
  <?php endif; ?>
</section>

<!-- Add / edit dialog (stays on this page) -->
<div class="pr-overlay" id="pmOverlay" hidden>
  <div class="pr-dialog" role="dialog" aria-modal="true" aria-labelledby="pmTitle">
    <div class="pr-dhead">
      <h2 id="pmTitle">Add a product</h2>
      <button class="pr-x" type="button" data-pm-close aria-label="Close">×</button>
    </div>

    <form method="post" action="<?= h($base) ?>" id="pmForm" novalidate>
      <div class="pr-dbody">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="return" value="<?= h($currentUrl) ?>">

        <div class="pr-field">
          <label for="pmName">Product name</label>
          <input class="pr-input" id="pmName" name="name" maxlength="100" required placeholder="e.g. Chicken Burger">
        </div>

        <?php if ($categories): ?>
          <div class="pr-field">
            <label for="pmCat">Category</label>
            <select class="pr-select" id="pmCat" name="category" required>
              <option value="">Choose a category</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= h($c) ?>"><?= h($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <p class="pr-warn">There are no categories yet. <a href="<?= h(BASE_URL . 'admin?page=categories') ?>">Add a category</a> first.</p>
        <?php endif; ?>

        <div class="pr-field">
          <label for="pmPrice">Price (<?= h(setting('currency', 'KES')) ?>)</label>
          <input class="pr-input num" id="pmPrice" name="price" inputmode="decimal" required placeholder="0.00">
        </div>

        <label class="pr-check"><input type="checkbox" name="active" value="1" id="pmActive" checked> Show in till</label>

        <p class="pr-hint">Names must be unique. Changing a price affects future sales only. Past receipts keep the price they were sold at.</p>
      </div>

      <div class="pr-dfoot">
        <button class="btn btn--ghost" type="button" data-pm-close>Cancel</button>
        <button class="btn btn--primary" type="submit" id="pmSubmit"<?= $categories ? '' : ' disabled' ?>>Add product</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var overlay = document.getElementById('pmOverlay');
  var form    = document.getElementById('pmForm');
  var title   = document.getElementById('pmTitle');
  var submit  = document.getElementById('pmSubmit');
  var lastFocus = null;

  function field(name) { return form.elements[name]; }

  function openProduct(d) {
    d = d || {};
    var editing = Number(d.id || 0) > 0;
    field('id').value       = editing ? String(d.id) : '0';
    field('name').value     = d.name || '';
    field('price').value    = d.price || '';
    field('active').checked = String(d.active) !== '0';
    if (field('category')) {
      field('category').value = d.category || '';
    }
    title.textContent  = editing ? 'Edit product' : 'Add a product';
    submit.textContent = editing ? 'Save changes' : 'Add product';
    lastFocus = document.activeElement;
    overlay.hidden = false;
    setTimeout(function () { field('name').focus(); }, 30);
  }

  function closeProduct() {
    overlay.hidden = true;
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.closest('[data-pm-new]')) {
      e.preventDefault();
      openProduct({});
      return;
    }
    var edit = t.closest('[data-pm-edit]');
    if (edit) {
      e.preventDefault();
      openProduct({
        id: edit.getAttribute('data-id'),
        name: edit.getAttribute('data-name'),
        category: edit.getAttribute('data-category'),
        price: edit.getAttribute('data-price'),
        active: edit.getAttribute('data-active')
      });
      return;
    }
    if (t.closest('[data-pm-close]') || t === overlay) {
      closeProduct();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) closeProduct();
  });

  /* Client-side check first, so obvious mistakes never leave the dialog. */
  form.addEventListener('submit', function (e) {
    var price = String(field('price').value).replace(/[,\s]/g, '');
    var cat   = field('category') ? field('category').value : '';
    if (!field('name').value.trim() || !cat || !/^\d{1,8}(\.\d{1,2})?$/.test(price) || Number(price) <= 0) {
      e.preventDefault();
      if (!field('name').value.trim()) field('name').focus();
      else if (!cat) field('category').focus();
      else field('price').focus();
      alert('Enter a name, choose a category, and enter a price greater than zero (for example 150 or 99.50).');
      return;
    }
    submit.disabled = true;
  });

  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* After a failed save, reopen the dialog with the values the user typed. */
  <?php if ($prefill !== null): ?>
  openProduct(<?= json_encode([
      'id'       => (int) $prefill['id'],
      'name'     => (string) $prefill['name'],
      'category' => (string) $prefill['category'],
      'price'    => (string) $prefill['price'],
      'active'   => (string) $prefill['active'],
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>);
  <?php elseif ($openNew): ?>
  /* Opened from Categories → Add product: preselect the category. */
  openProduct({ category: <?= json_encode($presetCat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?> });
  <?php endif; ?>
})();
</script>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>