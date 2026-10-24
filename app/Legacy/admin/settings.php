<?php
$pageTitle  = 'Settings';
$activePage = 'settings';

$h       = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pdo     = db();
$listUrl = BASE_URL . 'admin?page=settings';

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

/* ---------------------------------------------------------------
   Settings schema: label, input type, validation, default value
--------------------------------------------------------------- */
$schema = [
    'Store details' => [
        'shop_name'    => ['label' => 'Store name',        'type' => 'text',     'max' => 100, 'required' => true,  'default' => 'My Store'],
        'shop_address' => ['label' => 'Address',           'type' => 'textarea', 'max' => 255, 'default' => ''],
        'shop_phone'   => ['label' => 'Phone',             'type' => 'text',     'max' => 30,  'default' => ''],
        'shop_email'   => ['label' => 'Email',             'type' => 'email',    'max' => 120, 'default' => ''],
        'shop_website' => ['label' => 'Website',           'type' => 'text',     'max' => 120, 'default' => ''],
        'shop_kra_pin' => ['label' => 'KRA PIN / Tax ID',  'type' => 'text',     'max' => 30,  'default' => '', 'help' => 'Printed on receipts for tax compliance.'],
    ],
    'Receipt' => [
        'receipt_header' => ['label' => 'Header note',     'type' => 'text',     'max' => 150, 'default' => '', 'help' => 'Shown under the store name, e.g. a slogan.'],
        'receipt_footer' => ['label' => 'Footer message',  'type' => 'text',     'max' => 150, 'default' => 'Thank you for your visit!'],
        'receipt_terms'  => ['label' => 'Terms',           'type' => 'textarea', 'max' => 300, 'default' => '', 'help' => 'Small print at the bottom, e.g. return policy.'],
        'receipt_paper'  => ['label' => 'Default paper',   'type' => 'select',   'default' => '80',
                             'choices' => ['58' => '58 mm thermal', '80' => '80 mm thermal']],
        'show_qr'        => ['label' => 'Show QR code',    'type' => 'toggle',   'default' => '1',
                             'help' => 'Verification QR code printed at the bottom of the receipt.'],
    ],
    'Money & tax' => [
        'currency'  => ['label' => 'Currency code', 'type' => 'text', 'max' => 5, 'required' => true, 'default' => 'KES',
                        'pattern' => '/^[A-Z]{3,5}$/', 'pattern_msg' => 'Use 3 to 5 letters, e.g. KES or USD.',
                        'help' => 'Shown before every amount.'],
        'tax_label' => ['label' => 'Tax label',     'type' => 'text',   'max' => 20, 'default' => 'VAT'],
        'tax_rate'  => ['label' => 'Tax rate (%)',  'type' => 'number', 'min' => 0, 'maxv' => 100, 'default' => '0',
                        'help' => 'Set to 0 if you do not charge tax. Shown on receipts as included tax.'],
    ],
];

$defaults = [];
foreach ($schema as $fields) {
    foreach ($fields as $key => $f) {
        $defaults[$key] = $f['default'];
    }
}

/* ---------------------------------------------------------------
   Load current values
--------------------------------------------------------------- */
$current = $defaults;
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    if (array_key_exists($row['setting_key'], $current)) {
        $current[$row['setting_key']] = (string) $row['setting_value'];
    }
}

/* ---------------------------------------------------------------
   Validation
--------------------------------------------------------------- */
function stg_validate(array $f, string $v): ?string
{
    $label = $f['label'];

    if ($v === '') {
        return !empty($f['required']) ? "$label is required." : null;
    }
    if (isset($f['max']) && mb_strlen($v) > $f['max']) {
        return "$label must be {$f['max']} characters or fewer.";
    }

    switch ($f['type']) {
        case 'email':
            return filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.';

        case 'number':
            if (!is_numeric($v)) return "$label must be a number.";
            if ($v < ($f['min'] ?? 0) || $v > ($f['maxv'] ?? PHP_INT_MAX)) {
                return "$label must be between " . ($f['min'] ?? 0) . " and " . ($f['maxv'] ?? '∞') . ".";
            }
            return null;

        case 'select':
            return array_key_exists($v, $f['choices']) ? null : 'Choose a valid option.';
    }

    if (!empty($f['pattern']) && !preg_match($f['pattern'], $v)) {
        return $f['pattern_msg'] ?? "$label is not valid.";
    }
    return null;
}

/* ---------------------------------------------------------------
   Save
--------------------------------------------------------------- */
$errors = [];
$values = $current;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $errors['_form'] = 'Your session has expired. Refresh the page and try again.';
    } else {
        $clean = [];
        foreach ($schema as $fields) {
            foreach ($fields as $key => $f) {
                if ($f['type'] === 'toggle') {
                    $v = isset($_POST[$key]) ? '1' : '0';
                } else {
                    $v = trim((string) ($_POST[$key] ?? ''));
                    if ($key === 'currency') $v = strtoupper($v);
                }
                $clean[$key] = $v;

                $err = stg_validate($f, $v);
                if ($err !== null) $errors[$key] = $err;
            }
        }
        $values = $clean;

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $st = $pdo->prepare(
                    'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                );
                foreach ($clean as $key => $value) {
                    $st->execute([$key, $value]);
                }
                $pdo->commit();

                $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Settings saved. New receipts use these details.'];
                header('Location: ' . $listUrl);
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Settings save error: ' . $e->getMessage());
                $errors['_form'] = 'Could not save settings. Please try again.';
            }
        }
    }
}

/* ---------------------------------------------------------------
   Data for the preview and quick links
--------------------------------------------------------------- */
$lastPaid     = $pdo->query("SELECT id FROM orders WHERE status = 'paid' ORDER BY paid_at DESC LIMIT 1")->fetchColumn();
$userCount    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$taxRate = is_numeric($values['tax_rate']) ? (float) $values['tax_rate'] : 0;
$money   = fn($v) => number_format((float) $v, 2);

/* ---------------------------------------------------------------
   Field renderer
--------------------------------------------------------------- */
function stg_field(string $key, array $f, string $val, ?string $err, callable $h): void
{
    $id   = 'f-' . $key;
    $help = $f['help'] ?? '';
    $cls  = $err !== null ? ' is-invalid' : '';
    $req  = !empty($f['required']) ? ' required' : '';

    if ($f['type'] === 'toggle') {
        ?>
        <label class="st-switch-row" for="<?= $id ?>">
            <span>
                <strong><?= $h($f['label']) ?></strong>
                <?php if ($help): ?><small><?= $h($help) ?></small><?php endif; ?>
            </span>
            <span class="st-switch">
                <input type="checkbox" id="<?= $id ?>" name="<?= $h($key) ?>" value="1" <?= $val === '1' ? 'checked' : '' ?>>
                <i></i>
            </span>
        </label>
        <?php
        return;
    }
    ?>
    <div class="st-field">
        <label for="<?= $id ?>"><?= $h($f['label']) ?><?= !empty($f['required']) ? ' <span class="st-req">*</span>' : '' ?></label>

        <?php if ($f['type'] === 'textarea'): ?>
            <textarea class="st-input st-textarea<?= $cls ?>" id="<?= $id ?>" name="<?= $h($key) ?>" rows="3"
                      maxlength="<?= (int) $f['max'] ?>"<?= $req ?>><?= $h($val) ?></textarea>

        <?php elseif ($f['type'] === 'select'): ?>
            <select class="st-input<?= $cls ?>" id="<?= $id ?>" name="<?= $h($key) ?>">
                <?php foreach ($f['choices'] as $cv => $cl): ?>
                    <option value="<?= $h($cv) ?>" <?= $val === (string) $cv ? 'selected' : '' ?>><?= $h($cl) ?></option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($f['type'] === 'number'): ?>
            <input class="st-input<?= $cls ?>" id="<?= $id ?>" type="number" name="<?= $h($key) ?>" value="<?= $h($val) ?>"
                   min="<?= $h($f['min'] ?? 0) ?>" max="<?= $h($f['maxv'] ?? '') ?>" step="0.01" inputmode="decimal"<?= $req ?>>

        <?php else: ?>
            <input class="st-input<?= $cls ?>" id="<?= $id ?>" type="<?= $f['type'] === 'email' ? 'email' : 'text' ?>"
                   name="<?= $h($key) ?>" value="<?= $h($val) ?>" maxlength="<?= (int) ($f['max'] ?? 255) ?>"<?= $req ?>>
        <?php endif; ?>

        <?php if ($err !== null): ?>
            <small class="st-err"><?= $h($err) ?></small>
        <?php elseif ($help): ?>
            <small class="st-help"><?= $h($help) ?></small>
        <?php endif; ?>
    </div>
    <?php
}

require APP_ROOT . '/app/shared/views/admin/header.php';
?>

<style>
/* Layout */
.st-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:14px;align-items:start}
@media (max-width:1100px){.st-grid{grid-template-columns:1fr}}
.st-section{margin-bottom:14px;display:flex;flex-direction:column;gap:12px}
.st-section-head{display:flex;align-items:center;gap:10px}
.st-section-head h3{font-size:16px;font-weight:700;color:var(--text)}
.st-ico{width:32px;height:32px;border-radius:var(--radius-sm);background:var(--primary-soft);display:grid;place-items:center;font-size:15px}
.st-two{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}
@media (max-width:640px){.st-two{grid-template-columns:1fr}}
.st-wide{grid-column:1/-1}

/* Fields */
.st-field{display:flex;flex-direction:column;gap:6px;min-width:0;margin-bottom:14px}
.st-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.st-req{color:var(--danger,#dc2626)}
.st-input{width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;font-family:inherit;outline:none;transition:border-color .12s,box-shadow .12s}
.st-textarea{height:auto;min-height:80px;padding:10px 12px;resize:vertical;line-height:1.5}
.st-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.st-input.is-invalid{border-color:var(--danger,#dc2626)}
.st-help{font-size:12px;color:var(--text-3);line-height:1.4}
.st-err{font-size:12px;color:var(--danger,#b91c1c)}
.st-alert{padding:12px 14px;border-radius:var(--radius-sm);border:1px solid var(--danger,#fca5a5);background:var(--surface);color:var(--danger,#b91c1c);font-size:13.5px;margin-bottom:14px}

/* Toggle */
.st-switch-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;border-top:1px solid var(--border);cursor:pointer}
.st-switch-row strong{display:block;font-size:14px;color:var(--text);font-weight:600}
.st-switch-row small{display:block;margin-top:2px;color:var(--text-3);font-size:12px;line-height:1.4}
.st-switch{position:relative;width:44px;height:24px;flex-shrink:0}
.st-switch input{position:absolute;opacity:0;width:0;height:0}
.st-switch i{position:absolute;inset:0;background:var(--border);border-radius:999px;transition:background .2s}
.st-switch i::after{content:"";position:absolute;left:3px;top:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.st-switch input:checked + i{background:var(--primary)}
.st-switch input:checked + i::after{transform:translateX(20px)}
.st-switch input:focus-visible + i{outline:3px solid var(--primary-soft);outline-offset:2px}

/* Save bar */
.st-savebar{position:sticky;bottom:12px;z-index:5;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px 16px;box-shadow:var(--shadow-lg)}
.st-savebar .st-note{font-size:13px;color:var(--text-3)}
.st-btns{display:flex;gap:8px;flex-wrap:wrap}

/* Preview */
.st-preview{position:sticky;top:80px;display:flex;flex-direction:column;gap:12px}
@media (max-width:1100px){.st-preview{position:static}}
.st-h{font-size:16px;margin-top:4px;color:var(--text)}
.st-tabs{display:flex;gap:6px}
.st-tabs button{flex:1;height:34px;border:1px solid var(--border);background:var(--surface-2);color:var(--text-2);border-radius:var(--radius-sm);cursor:pointer;font-size:12.5px;font-weight:600;font-family:inherit}
.st-tabs button.is-on{background:var(--primary-soft);color:var(--primary);border-color:var(--primary)}

/* Receipt paper stays white on purpose so it reads like a printed slip */
.rcpt{font-family:"Courier New",ui-monospace,monospace;font-size:12px;color:#111;background:#fff;border:1px dashed var(--border);border-radius:var(--radius-sm);padding:14px 12px;margin:0 auto;max-width:280px;transition:max-width .2s}
.rcpt.p58{max-width:220px;font-size:11px}
.rcpt .c{text-align:center}
.rcpt .big{font-size:15px;font-weight:700;letter-spacing:.03em}
.rcpt hr{border:0;border-top:1px dashed #999;margin:8px 0}
.rcpt .row{display:flex;justify-content:space-between;gap:6px}
.rcpt .tot{font-weight:700;font-size:13px}
.rcpt .qr-box{width:84px;height:84px;margin:8px auto;border:2px solid #111;display:grid;place-items:center;font-size:10px;color:#555;background:repeating-linear-gradient(45deg,#fff 0 4px,#eee 4px 8px)}
.rcpt .sm{color:#444;font-size:10.5px}

.st-links a{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);font-size:13.5px;color:var(--text);text-decoration:none}
.st-links a:last-child{border-bottom:0}
.st-links a:hover{color:var(--primary)}
.st-links span{color:var(--text-3);font-size:12.5px}
</style>

<div class="page-head">
  <div>
    <p class="eyebrow">Store</p>
    <h1 class="page-title">Settings</h1>
    <p class="page-sub">Store details, receipt layout, and tax. Changes apply to new receipts immediately.</p>
  </div>
</div>

<?php if (isset($errors['_form'])): ?>
  <div class="st-alert" role="alert"><?= $h($errors['_form']) ?></div>
<?php elseif ($errors): ?>
  <div class="st-alert" role="alert">Please fix the highlighted fields and save again.</div>
<?php endif; ?>

<form method="post" action="<?= $h($listUrl) ?>" class="st-grid" id="settingsForm" novalidate>
  <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">

  <div>
    <?php
    $icons = ['Store details' => '', 'Receipt' => '', 'Money & tax' => ''];
    foreach ($schema as $group => $fields):
    ?>
      <section class="card st-section">
        <div class="st-section-head">
          <div class="st-ico"><?= $icons[$group] ?? '⚙' ?></div>
          <h3><?= $h($group) ?></h3>
        </div>

        <?php
        $wideFields = [];
        foreach ($fields as $key => $f) {
            $wide = in_array($f['type'], ['textarea', 'toggle'], true) || ($f['max'] ?? 0) > 60;
            if ($f['type'] !== 'toggle') {
                $wideFields[] = [$key, $f, $wide];
            }
        }
        ?>

        <div class="st-two">
          <?php foreach ($wideFields as [$key, $f, $wide]): ?>
            <div class="<?= $wide ? 'st-wide' : '' ?>">
              <?php stg_field($key, $f, $values[$key], $errors[$key] ?? null, $h); ?>
            </div>
          <?php endforeach; ?>
        </div>

        <?php foreach ($fields as $key => $f):
          if ($f['type'] !== 'toggle') continue;
          stg_field($key, $f, $values[$key], $errors[$key] ?? null, $h);
        endforeach; ?>
      </section>
    <?php endforeach; ?>

    <div class="st-savebar">
      <span class="st-note">Fields marked <span class="st-req">*</span> are required.</span>
      <div class="st-btns">
        <a class="btn btn--ghost" href="<?= $h($listUrl) ?>">Discard changes</a>
        <button class="btn btn--primary" type="submit">Save settings</button>
      </div>
    </div>
  </div>

  <aside>
    <section class="card st-preview">
      <div>
        <p class="eyebrow">Preview</p>
        <h2 class="st-h">Receipt preview</h2>
      </div>

      <div class="st-tabs" role="tablist">
        <button type="button" class="is-on" data-paper="80">80 mm</button>
        <button type="button" data-paper="58">58 mm</button>
      </div>

      <div class="rcpt" id="rcptBox">
        <div class="c">
          <div class="big" id="pvName"><?= $h(strtoupper($values['shop_name'])) ?></div>
          <div class="sm" id="pvHeader"><?= $h($values['receipt_header']) ?></div>
          <div class="sm" id="pvAddress"><?= $h($values['shop_address']) ?></div>
          <div class="sm" id="pvPhone"><?= $values['shop_phone'] !== '' ? 'Tel: ' . $h($values['shop_phone']) : '' ?></div>
          <div class="sm" id="pvKra"><?= $values['shop_kra_pin'] !== '' ? 'PIN: ' . $h($values['shop_kra_pin']) : '' ?></div>
        </div>
        <hr>
        <div class="row"><span>Coffee x2</span><span><?= $money(300) ?></span></div>
        <div class="row"><span>Burger x1</span><span><?= $money(850) ?></span></div>
        <hr>
        <div class="row"><span>Subtotal</span><span><?= $money(1150) ?></span></div>
        <div class="row tot"><span>TOTAL (<span id="pvCur"><?= $h($values['currency']) ?></span>)</span><span><?= $money(1150) ?></span></div>
        <div class="row sm" id="pvTaxRow" style="<?= $taxRate > 0 ? '' : 'display:none' ?>">
          <span><span id="pvTaxLabel"><?= $h($values['tax_label']) ?></span> <span id="pvTaxRate"><?= rtrim(rtrim(number_format($taxRate, 2), '0'), '.') ?></span>% incl.</span>
          <span><?= $money($taxRate > 0 ? 1150 * $taxRate / (100 + $taxRate) : 0) ?></span>
        </div>
        <hr>
        <div class="c sm" id="pvFooter"><?= $h($values['receipt_footer']) ?></div>
        <div class="qr-box" id="pvQr" style="<?= $values['show_qr'] === '1' ? '' : 'display:none' ?>">QR</div>
        <div class="c sm" id="pvTerms"><?= $h($values['receipt_terms']) ?></div>
      </div>

      <p class="st-help">Preview updates as you type. Save to apply it to real receipts.</p>

      <div class="st-links">
        <?php if ($lastPaid): ?>
          <a href="<?= $h(BASE_URL . 'cashier?receipt=' . (int) $lastPaid) ?>" target="_blank" rel="noopener">
            Open latest receipt <span>#<?= (int) $lastPaid ?></span>
          </a>
        <?php endif; ?>
        <a href="<?= $h(BASE_URL . 'admin?page=users') ?>">Manage users <span><?= $userCount ?></span></a>
        <a href="<?= $h(BASE_URL . 'admin?page=products') ?>">Products <span><?= $productCount ?></span></a>
      </div>
    </section>
  </aside>
</form>

<script>
(function () {
  var form = document.getElementById('settingsForm');
  var box  = document.getElementById('rcptBox');
  var $    = function (id) { return document.getElementById(id); };

  function val(name) {
    var el = form.elements[name];
    if (!el) return '';
    if (el.type === 'checkbox') return el.checked ? '1' : '0';
    return el.value.trim();
  }

  function sync() {
    var name = val('shop_name');
    $('pvName').textContent    = (name || 'YOUR STORE').toUpperCase();
    $('pvHeader').textContent  = val('receipt_header');
    $('pvAddress').textContent = val('shop_address');
    $('pvPhone').textContent   = val('shop_phone') ? 'Tel: ' + val('shop_phone') : '';
    $('pvKra').textContent     = val('shop_kra_pin') ? 'PIN: ' + val('shop_kra_pin') : '';
    $('pvCur').textContent     = (val('currency') || 'KES').toUpperCase();
    $('pvFooter').textContent  = val('receipt_footer');
    $('pvTerms').textContent   = val('receipt_terms');
    $('pvQr').style.display    = val('show_qr') === '1' ? '' : 'none';

    var rate = parseFloat(val('tax_rate')) || 0;
    $('pvTaxRow').style.display = rate > 0 ? '' : 'none';
    $('pvTaxLabel').textContent = val('tax_label') || 'VAT';
    $('pvTaxRate').textContent  = rate.toString();
  }

  form.addEventListener('input', sync);
  form.addEventListener('change', sync);

  document.querySelectorAll('.st-tabs button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var paper = btn.getAttribute('data-paper');
      document.querySelectorAll('.st-tabs button').forEach(function (b) {
        b.classList.toggle('is-on', b === btn);
      });
      box.classList.toggle('p58', paper === '58');
    });
  });

  // Warn before leaving with unsaved changes
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });
  form.addEventListener('submit', function () { dirty = false; });

  sync();
})();
</script>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>