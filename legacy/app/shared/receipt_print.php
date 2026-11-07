<?php
/* Print-only page: one receipt and nothing else.
   Route: /receipt/print?id=ID&paper=58|80 */

$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$me = $_SESSION['user'] ?? null;
if (!$me) {
    header('Location: ' . BASE_URL . 'login');
    exit;
}

$pid = (int) ($_GET['id'] ?? 0);

$chk = db()->prepare('SELECT user_id FROM orders WHERE id = ?');
$chk->execute([$pid]);
$ownerId = $chk->fetchColumn();

if ($ownerId === false) {
    http_response_code(404);
    exit('Receipt not found.');
}
if ($me['role'] !== 'admin' && (int) $ownerId !== (int) $me['id']) {
    http_response_code(403);
    exit('You can only print your own receipts.');
}

// Variables used by receipt.php
$rid   = $pid;
$paper = in_array($_GET['paper'] ?? '', ['58', '80'], true)
    ? $_GET['paper']
    : (setting('receipt_paper', '80') === '58' ? '58' : '80');

$showQr   = setting('show_qr', '1') === '1';
$shopName = setting('shop_name', 'POS');
$rcNo     = 'RCP-' . str_pad((string) $pid, 6, '0', STR_PAD_LEFT);
$base     = BASE_URL . 'receipt/print?id=' . $pid;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print <?= $h($rcNo) ?> | <?= $h($shopName) ?></title>
    <style>
        html, body { margin: 0; background: #eef2f0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        body { padding: 1rem; }
        .bar {
            display: flex; gap: .5rem; justify-content: center; align-items: center; flex-wrap: wrap;
            max-width: 360px; margin: 0 auto 1rem; font-size: .85rem; color: #334155;
        }
        .bar button, .bar a.btn {
            padding: .55rem .9rem; border-radius: 9px; border: 1px solid #cbd5e1; background: #fff;
            font: inherit; font-weight: 600; cursor: pointer; color: #0f172a; text-decoration: none;
        }
        .bar button.primary { background: #198754; border-color: #198754; color: #fff; }
        .seg { display: inline-flex; border: 1px solid #cbd5e1; border-radius: 9px; overflow: hidden; }
        .seg a { padding: .4rem .7rem; font-size: .8rem; color: #0f172a; text-decoration: none; background: #fff; }
        .seg a.on { background: #0f5132; color: #fff; }

        @media print {
            html, body { background: #fff; padding: 0; }
            .bar { display: none !important; }
            @page { margin: 3mm; }
        }
    </style>
</head>
<body>

<div class="bar">
    <button class="primary" type="button" onclick="window.print()">Print</button>
    <div class="seg">
        <a class="<?= $paper === '58' ? 'on' : '' ?>" href="<?= $h($base . '&paper=58') ?>">58 mm</a>
        <a class="<?= $paper === '80' ? 'on' : '' ?>" href="<?= $h($base . '&paper=80') ?>">80 mm</a>
    </div>
    <a class="btn" href="javascript:window.close()">Close</a>
</div>

<?php require APP_ROOT . '/app/shared/views/receipt.php'; ?>

<?php if ($showQr): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
(function () {
    document.querySelectorAll('[data-qr]').forEach(function (el) {
        try {
            if (typeof qrcode !== 'function') { el.hidden = true; return; }
            var q = qrcode(0, 'M');
            q.addData(el.dataset.qr);
            q.make();
            el.innerHTML = q.createSvgTag({ cellSize: 3, margin: 0 });
        } catch (e) { el.hidden = true; }
    });
})();
</script>
<?php endif; ?>

<script>
(function () {
    window.onafterprint = function () { if (window.opener) window.close(); };
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
})();
</script>
</body>
</html>