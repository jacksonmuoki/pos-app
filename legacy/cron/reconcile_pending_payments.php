<?php
/*
 * Run every minute:
 * * * * * /usr/bin/php /path/to/pos/cron/reconcile_pending_payments.php >> /path/to/pos/logs/reconcile.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/PaymentService.php';

// Prevent overlapping runs.
$lock = fopen(__DIR__ . '/../logs/reconcile.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$config  = require __DIR__ . '/../app/includes/payhero.php';
$service = new PaymentService(db(), $config);

$checked = $service->reconcileStale(50);
echo date('c') . " checked {$checked} payment(s)\n";