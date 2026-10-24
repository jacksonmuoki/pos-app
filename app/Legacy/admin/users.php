<?php
/* Admin: users. Full staff profiles (names, contact, personal and work details),
   login, PIN, roles, lockouts, and safe deactivation. */

if (!defined('US_ROLES')) {
    define('US_ROLES', ['admin' => 'Admin', 'cashier' => 'Cashier', 'waiter' => 'Waiter']);
}

if (!function_exists('us_flash')) {
    function us_flash(string $type, string $msg): void
    {
        $_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
    }
}

if (!function_exists('us_go')) {
    function us_go(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('us_text')) {
    function us_text(string $s, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $s)), 0, $max);
    }
}

if (!function_exists('us_name_ok')) {
    function us_name_ok(string $s): bool
    {
        return (bool) preg_match("/^[\\p{L}][\\p{L}\\s'.-]{0,49}$/u", $s);
    }
}

if (!function_exists('us_login_ok')) {
    function us_login_ok(string $s): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._-]{3,40}$/', $s);
    }
}

/** Returns '' when empty, the cleaned number when valid, or false when invalid. */
if (!function_exists('us_phone')) {
    function us_phone(string $raw)
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (!preg_match('/^\+?[0-9][0-9\s\-()]{6,18}$/', $raw)) {
            return false;
        }
        $digits = preg_replace('/[^0-9+]/', '', $raw);
        return str_starts_with($digits, '+') ? $digits : $digits;
    }
}

/** Returns '' when empty, a Y-m-d date when valid and not in the future, or false. */
if (!function_exists('us_date_clean')) {
    function us_date_clean(string $s)
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
        if (!$d || $d->format('Y-m-d') !== $s || $d > new DateTimeImmutable('today')) {
            return false;
        }
        return $s;
    }
}

if (!function_exists('us_initials')) {
    function us_initials(string $first, string $last, string $fallback): string
    {
        $i = strtoupper(mb_substr(trim($first), 0, 1) . mb_substr(trim($last), 0, 1));
        if ($i === '') {
            $parts = preg_split('/\s+/', trim($fallback)) ?: [];
            $i = strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
        }
        return $i;
    }
}

if (!function_exists('us_active_admins')) {
    function us_active_admins(PDO $pdo, int $exclude = 0): int
    {
        $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND id <> ?");
        $s->execute([$exclude]);
        return (int) $s->fetchColumn();
    }
}

/* ============================================================
   POST actions
   ============================================================ */

$pdo  = db();
$base = BASE_URL . 'admin/users';
$me   = (int) ($_SESSION['user']['id'] ?? 0);

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        us_flash('error', 'Your session expired. Please try again.');
        us_go($base);
    }

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        switch ($action) {

            case 'save':
                $editing = $id > 0;
                $form    = $base . ($editing ? '?edit=' . $id : '?new=1');
                $fail    = static function (string $msg) use ($form): void {
                    us_flash('error', $msg);
                    us_go($form);
                };

                $first   = us_text((string) ($_POST['first_name'] ?? ''), 50);
                $last    = us_text((string) ($_POST['last_name'] ?? ''), 50);
                $login   = trim((string) ($_POST['login'] ?? ''));
                $role    = (string) ($_POST['role'] ?? '');
                $active  = !empty($_POST['active']) ? 1 : 0;
                $pin     = trim((string) ($_POST['pin'] ?? ''));
                $phone   = us_phone((string) ($_POST['phone'] ?? ''));
                $email   = strtolower(trim((string) ($_POST['email'] ?? '')));
                $idNo    = strtoupper(us_text((string) ($_POST['id_number'] ?? ''), 20));
                $dob     = us_date_clean((string) ($_POST['date_of_birth'] ?? ''));
                $gender  = (string) ($_POST['gender'] ?? '');
                $address = us_text((string) ($_POST['address'] ?? ''), 255);
                $hire    = us_date_clean((string) ($_POST['hire_date'] ?? ''));
                $emName  = us_text((string) ($_POST['emergency_name'] ?? ''), 100);
                $emPhone = us_phone((string) ($_POST['emergency_phone'] ?? ''));
                $notes   = us_text((string) ($_POST['notes'] ?? ''), 500);

                if ($first === '' || !us_name_ok($first)) {
                    $fail('Enter the first name. Letters, spaces, apostrophes and hyphens only.');
                }
                if ($last === '' || !us_name_ok($last)) {
                    $fail('Enter the last name. Letters, spaces, apostrophes and hyphens only.');
                }
                if (!us_login_ok($login)) {
                    $fail('The username must be 3 to 40 letters, numbers, dots, dashes or underscores.');
                }
                if (!isset(US_ROLES[$role])) {
                    $fail('Choose a role.');
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail('Enter a valid email address.');
                }
                if ($phone === false) {
                    $fail('Enter a valid phone number, for example 0712 345 678.');
                }
                if ($emPhone === false) {
                    $fail('Enter a valid emergency contact number.');
                }
                if ($dob === false) {
                    $fail('Enter a valid date of birth.');
                }
                if ($dob !== '' && (new DateTimeImmutable($dob))->diff(new DateTimeImmutable('today'))->y < 16) {
                    $fail('Staff must be at least 16 years old.');
                }
                if ($hire === false) {
                    $fail('Enter a valid hire date.');
                }
                if ($gender !== '' && !in_array($gender, ['male', 'female', 'other'], true)) {
                    $fail('Choose a gender, or leave it blank.');
                }
                if ($pin !== '' && !preg_match('/^\d{4,6}$/', $pin)) {
                    $fail('The PIN must be 4 to 6 digits.');
                }
                if (!$editing && $pin === '') {
                    $fail('Set a PIN for the new user.');
                }

                $dup = $pdo->prepare('SELECT id FROM users WHERE LOWER(login) = LOWER(?) AND id <> ? LIMIT 1');
                $dup->execute([$login, $id]);
                if ($dup->fetch()) {
                    $fail('That username is already taken.');
                }

                $n = static fn($v) => ($v === '' || $v === false) ? null : $v;

                $data = [
                    'name'            => trim($first . ' ' . $last),
                    'first_name'      => $first,
                    'last_name'       => $last,
                    'login'           => $login,
                    'phone'           => $n($phone),
                    'email'           => $n($email),
                    'id_number'       => $n($idNo),
                    'date_of_birth'   => $n($dob),
                    'gender'          => $n($gender),
                    'address'         => $n($address),
                    'hire_date'       => $n($hire),
                    'emergency_name'  => $n($emName),
                    'emergency_phone' => $n($emPhone),
                    'notes'           => $n($notes),
                    'role'            => $role,
                    'active'          => $active,
                ];

                if ($editing) {
                    $cur = $pdo->prepare('SELECT role, active FROM users WHERE id = ?');
                    $cur->execute([$id]);
                    $old = $cur->fetch();
                    if (!$old) {
                        us_flash('error', 'That user no longer exists.');
                        us_go($base);
                    }

                    $losesAdmin = $old['role'] === 'admin' && ($role !== 'admin' || !$active);
                    if ($losesAdmin && us_active_admins($pdo, $id) === 0) {
                        $fail('At least one active admin must remain. Make another person an admin first.');
                    }
                    if ($id === $me && !$active) {
                        $fail('You cannot deactivate your own account.');
                    }
                    if ($id === $me && $role !== 'admin') {
                        $fail('You cannot remove your own admin role.');
                    }

                    $set    = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
                    $params = array_values($data);
                    if ($pin !== '') {
                        $set     .= ', pin_hash = ?';
                        $params[] = password_hash($pin, PASSWORD_DEFAULT);
                    }
                    $params[] = $id;
                    $pdo->prepare("UPDATE users SET $set WHERE id = ?")->execute($params);
                    us_flash('success', $data['name'] . '\'s profile was updated.');
                } else {
                    $cols   = implode(', ', array_keys($data)) . ', pin_hash';
                    $marks  = implode(', ', array_fill(0, count($data) + 1, '?'));
                    $params = array_values($data);
                    $params[] = password_hash($pin, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO users ($cols) VALUES ($marks)")->execute($params);
                    us_flash('success', $data['name'] . ' was added. Give them their username and PIN in person.');
                }
                us_go($base);

            case 'toggle':
                if ($id === $me) {
                    us_flash('error', 'You cannot deactivate your own account.');
                    break;
                }
                $cur = $pdo->prepare('SELECT role, active FROM users WHERE id = ?');
                $cur->execute([$id]);
                $u = $cur->fetch();
                if (!$u) {
                    us_flash('error', 'That user no longer exists.');
                    break;
                }
                if ($u['role'] === 'admin' && (int) $u['active'] === 1 && us_active_admins($pdo, $id) === 0) {
                    us_flash('error', 'At least one active admin must remain.');
                    break;
                }
                $pdo->prepare('UPDATE users SET active = 1 - active WHERE id = ?')->execute([$id]);
                us_flash('success', (int) $u['active'] ? 'Account deactivated. They can no longer sign in.' : 'Account reactivated.');
                break;

            case 'unlock':
                $pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?')->execute([$id]);
                us_flash('success', 'Account unlocked.');
                break;

            case 'reset':
                $pin = trim((string) ($_POST['pin'] ?? ''));
                if (!preg_match('/^\d{4,6}$/', $pin)) {
                    us_flash('error', 'The new PIN must be 4 to 6 digits.');
                    break;
                }
                $pdo->prepare('UPDATE users SET pin_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?')
                    ->execute([password_hash($pin, PASSWORD_DEFAULT), $id]);
                us_flash('success', 'PIN reset and any lock cleared. Give the new PIN to the user in person.');
                break;

            case 'delete':
                if ($id === $me) {
                    us_flash('error', 'You cannot delete your own account.');
                    break;
                }
                $cur = $pdo->prepare('SELECT role, active, name FROM users WHERE id = ?');
                $cur->execute([$id]);
                $u = $cur->fetch();
                if (!$u) {
                    us_flash('error', 'That user no longer exists.');
                    break;
                }
                if ($u['role'] === 'admin' && (int) $u['active'] === 1 && us_active_admins($pdo, $id) === 0) {
                    us_flash('error', 'At least one active admin must remain.');
                    break;
                }

                // Keep sales and payment history intact. Users with activity are deactivated instead.
                $used = $pdo->prepare('SELECT (SELECT COUNT(*) FROM orders WHERE user_id = ?) + (SELECT COUNT(*) FROM payments WHERE user_id = ?)');
                $used->execute([$id, $id]);
                if ((int) $used->fetchColumn() > 0) {
                    $pdo->prepare('UPDATE users SET active = 0 WHERE id = ?')->execute([$id]);
                    us_flash('warning', $u['name'] . ' has sales history, so the account was deactivated instead of deleted.');
                } else {
                    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                    us_flash('success', 'User deleted.');
                }
                break;
        }
    } catch (PDOException $e) {
        error_log('Users admin error: ' . $e->getMessage());
        us_flash('error', 'Could not save. Check the PHP error log for details.');
    }

    us_go($base);
}

/* ============================================================
   Filters and list
   ============================================================ */

$q      = us_text((string) ($_GET['q'] ?? ''), 60);
$role   = isset(US_ROLES[$_GET['role'] ?? '']) ? (string) $_GET['role'] : '';
$status = in_array($_GET['status'] ?? '', ['active', 'inactive', 'locked'], true) ? (string) $_GET['status'] : '';

$where  = [];
$params = [];
if ($q !== '') {
    $like     = '%' . addcslashes($q, '%_\\') . '%';
    $where[]  = '(name LIKE ? OR login LIKE ? OR phone LIKE ? OR email LIKE ? OR id_number LIKE ?)';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($role !== '') {
    $where[]  = 'role = ?';
    $params[] = $role;
}
if ($status === 'active')   $where[] = 'active = 1';
if ($status === 'inactive') $where[] = 'active = 0';
if ($status === 'locked')   $where[] = 'locked_until > NOW()';
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$users   = [];
$stats   = ['total' => 0, 'active' => 0, 'admins' => 0, 'staff' => 0, 'locked' => 0];
$dbError = false;

try {
    $st = $pdo->query(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(active = 1), 0) AS active,
                COALESCE(SUM(role = 'admin' AND active = 1), 0) AS admins,
                COALESCE(SUM(role IN ('cashier','waiter') AND active = 1), 0) AS staff,
                COALESCE(SUM(locked_until > NOW()), 0) AS locked
         FROM users"
    )->fetch();
    $stats = [
        'total'  => (int) $st['total'],
        'active' => (int) $st['active'],
        'admins' => (int) $st['admins'],
        'staff'  => (int) $st['staff'],
        'locked' => (int) $st['locked'],
    ];

    $l = $pdo->prepare(
        "SELECT u.id, u.name, u.first_name, u.last_name, u.login, u.phone, u.email, u.role, u.active,
                u.last_login_at, u.failed_logins, u.locked_until,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_n,
                (SELECT COUNT(*) FROM payments p WHERE p.user_id = u.id) AS pays_n
         FROM users u $W
         ORDER BY u.active DESC, FIELD(u.role, 'admin', 'cashier', 'waiter'), u.first_name, u.last_name
         LIMIT 300"
    );
    $l->execute($params);
    $users = $l->fetchAll();
} catch (PDOException $e) {
    error_log('Users list failed: ' . $e->getMessage());
    $dbError = true;
}

/* ============================================================
   Edit or new
   ============================================================ */

$editId  = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editId > 0) {
    try {
        $s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $s->execute([$editId]);
        $editing = $s->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Users edit load failed: ' . $e->getMessage());
        $dbError = true;
    }
}
$formOpen = $editing !== null || isset($_GET['new']);

$fv = $editing ?: [
    'id' => 0, 'first_name' => '', 'last_name' => '', 'login' => '', 'phone' => '', 'email' => '',
    'id_number' => '', 'date_of_birth' => '', 'gender' => '', 'address' => '', 'hire_date' => '',
    'emergency_name' => '', 'emergency_phone' => '', 'notes' => '', 'role' => 'cashier', 'active' => 1,
];
$v = static fn(string $k): string => h((string) ($fv[$k] ?? ''));

$listUrl = fn(array $extra = []): string => $base . ($extra ? '?' . http_build_query($extra) : '');

/* ============================================================
   Layout
   ============================================================ */

$pageTitle  = 'Users';
$activePage = 'users';
$crumbs     = ['Admin' => BASE_URL . 'admin', 'Users' => null];

require APP_ROOT . '/app/shared/views/admin/header.php';
?>

<style>
.us-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}
.us-kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;flex-direction:column;gap:6px;min-width:0}
.us-kpi-l{font-size:12.5px;color:var(--text-3);font-weight:500}
.us-kpi-v{font-size:24px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.us-kpi-v.is-bad{color:var(--danger)}
.us-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;gap:14px;min-width:0}
.us-head{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap}
.us-title{font-size:16px;margin-top:3px}
.us-muted{font-size:12.5px;color:var(--text-3)}
.us-sum{cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:16px}
.us-sum::-webkit-details-marker{display:none}
.us-fieldset{border:1px solid var(--border);border-radius:var(--radius);padding:14px;display:flex;flex-direction:column;gap:12px;min-width:0}
.us-fieldset legend{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-3);padding:0 6px}
.us-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.us-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.us-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.us-field.wide{grid-column:1/-1}
.us-field label{font-size:12px;font-weight:600;color:var(--text-2)}
.us-in{width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:14px;outline:none}
textarea.us-in{height:auto;min-height:70px;padding:10px 12px;resize:vertical}
.us-in:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
.us-check{display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--text-2);height:40px}
.us-check input{width:18px;height:18px;accent-color:var(--primary)}
.us-hint{font-size:12px;color:var(--text-3)}
.us-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.us-sm{min-height:30px;padding:4px 10px;font-size:12.5px}
.us-filter{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end}
.us-person{display:flex;align-items:center;gap:10px;min-width:0}
.us-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:12px;background:linear-gradient(135deg,var(--primary),var(--purple));flex:none}
.us-name{font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.us-login{font-size:12px;color:var(--text-3);font-family:ui-monospace,monospace}
.us-role{display:inline-block;padding:2px 9px;border-radius:999px;font-size:11.5px;font-weight:600}
.us-role--admin{background:var(--purple-soft);color:var(--purple)}
.us-role--cashier{background:var(--info-soft);color:var(--info)}
.us-role--waiter{background:var(--warning-soft);color:var(--warning)}
.us-notice{padding:12px 14px;border-radius:var(--radius);background:var(--warning-soft);color:var(--warning);border:1px solid var(--warning);font-weight:500}
.us-rules{display:grid;gap:6px;font-size:12.5px;color:var(--text-3)}
.us-pop{position:absolute;right:0;top:calc(100% + 6px);z-index:10;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px;display:flex;flex-direction:column;gap:8px;width:240px;box-shadow:var(--shadow-lg)}
@media (max-width:1280px){.us-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:1024px){.us-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.us-filter{grid-template-columns:1fr 1fr}}
@media (max-width:640px){.us-grid,.us-grid-2,.us-filter{grid-template-columns:1fr}.us-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.us-kpi-v{font-size:20px}}
</style>

<!-- PAGE HEAD -->
<div class="page-head">
  <div>
    <p class="eyebrow">Admin</p>
    <h1 class="page-title">Users</h1>
    <p class="page-sub">Staff profiles, contact details, roles and sign-in access.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn--primary" href="<?= h($listUrl(['new' => 1])) ?>#user-form">+ New user</a>
  </div>
</div>

<?php if ($dbError): ?>
  <div class="us-notice" role="alert">User data could not be loaded. Check the PHP error log.</div>
<?php endif; ?>

<!-- KPIs -->
<section class="us-kpis" aria-label="User summary">
  <article class="us-kpi"><span class="us-kpi-l">Total users</span><span class="us-kpi-v"><?= (int) $stats['total'] ?></span></article>
  <article class="us-kpi"><span class="us-kpi-l">Can sign in</span><span class="us-kpi-v"><?= (int) $stats['active'] ?></span></article>
  <article class="us-kpi"><span class="us-kpi-l">Active admins</span><span class="us-kpi-v"><?= (int) $stats['admins'] ?></span></article>
  <article class="us-kpi"><span class="us-kpi-l">Active staff</span><span class="us-kpi-v"><?= (int) $stats['staff'] ?></span></article>
  <article class="us-kpi"><span class="us-kpi-l">Locked out</span><span class="us-kpi-v <?= $stats['locked'] > 0 ? 'is-bad' : '' ?>"><?= (int) $stats['locked'] ?></span></article>
</section>

<!-- ADD / EDIT -->
<section class="us-card" id="user-form" aria-labelledby="form-title">
  <details <?= $formOpen ? 'open' : '' ?>>
    <summary class="us-sum">
      <span id="form-title"><?= $editing ? 'Edit ' . h($editing['name']) : 'Add a user' ?></span>
      <span class="us-muted"><?= $formOpen ? 'close' : 'open' ?></span>
    </summary>

    <form method="post" action="<?= h($base) ?>" autocomplete="off" style="margin-top:16px;display:flex;flex-direction:column;gap:16px">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) $fv['id'] ?>">

      <fieldset class="us-fieldset">
        <legend>Personal</legend>
        <div class="us-grid">
          <div class="us-field">
            <label for="uFirst">First name</label>
            <input class="us-in" id="uFirst" name="first_name" maxlength="50" required value="<?= $v('first_name') ?>" placeholder="e.g. Grace">
          </div>
          <div class="us-field">
            <label for="uLast">Last name</label>
            <input class="us-in" id="uLast" name="last_name" maxlength="50" required value="<?= $v('last_name') ?>" placeholder="e.g. Wanjiru">
          </div>
          <div class="us-field">
            <label for="uGender">Gender</label>
            <select class="us-in" id="uGender" name="gender">
              <option value="">Not specified</option>
              <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $label): ?>
                <option value="<?= h($k) ?>" <?= $fv['gender'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="us-field">
            <label for="uDob">Date of birth</label>
            <input class="us-in" id="uDob" type="date" name="date_of_birth" value="<?= $v('date_of_birth') ?>">
          </div>
          <div class="us-field">
            <label for="uId">National ID number</label>
            <input class="us-in" id="uId" name="id_number" maxlength="20" value="<?= $v('id_number') ?>" placeholder="Optional">
          </div>
          <div class="us-field">
            <label for="uHire">Hire date</label>
            <input class="us-in" id="uHire" type="date" name="hire_date" value="<?= $v('hire_date') ?>">
          </div>
        </div>
      </fieldset>

      <fieldset class="us-fieldset">
        <legend>Contact</legend>
        <div class="us-grid">
          <div class="us-field">
            <label for="uPhone">Phone number</label>
            <input class="us-in" id="uPhone" type="tel" name="phone" maxlength="20" value="<?= $v('phone') ?>" placeholder="0712 345 678" inputmode="tel">
          </div>
          <div class="us-field">
            <label for="uEmail">Email</label>
            <input class="us-in" id="uEmail" type="email" name="email" maxlength="120" value="<?= $v('email') ?>" placeholder="name@example.com">
          </div>
          <div class="us-field">
            <label for="uEmName">Emergency contact name</label>
            <input class="us-in" id="uEmName" name="emergency_name" maxlength="100" value="<?= $v('emergency_name') ?>">
          </div>
          <div class="us-field">
            <label for="uEmPhone">Emergency contact phone</label>
            <input class="us-in" id="uEmPhone" type="tel" name="emergency_phone" maxlength="20" value="<?= $v('emergency_phone') ?>" inputmode="tel">
          </div>
          <div class="us-field wide">
            <label for="uAddr">Home address</label>
            <input class="us-in" id="uAddr" name="address" maxlength="255" value="<?= $v('address') ?>">
          </div>
        </div>
      </fieldset>

      <fieldset class="us-fieldset">
        <legend>Work and sign-in</legend>
        <div class="us-grid">
          <div class="us-field">
            <label for="uRole">Role</label>
            <select class="us-in" id="uRole" name="role">
              <?php foreach (US_ROLES as $k => $label): ?>
                <option value="<?= h($k) ?>" <?= $fv['role'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="us-field">
            <label for="uLogin">Username</label>
            <input class="us-in" id="uLogin" name="login" maxlength="40" required value="<?= $v('login') ?>" placeholder="e.g. grace" autocapitalize="off">
          </div>
          <div class="us-field">
            <label for="uPin"><?= $editing ? 'New PIN (leave blank to keep)' : 'PIN' ?></label>
            <input class="us-in" id="uPin" type="password" name="pin" inputmode="numeric" pattern="[0-9]{4,6}"
                   autocomplete="new-password" <?= $editing ? '' : 'required' ?> placeholder="4 to 6 digits">
          </div>
          <div class="us-field">
            <label>Access</label>
            <label class="us-check"><input type="checkbox" name="active" value="1" <?= (int) $fv['active'] ? 'checked' : '' ?>> Can sign in</label>
          </div>
        </div>
      </fieldset>

      <fieldset class="us-fieldset">
        <legend>Notes</legend>
        <div class="us-field">
          <label for="uNotes">Internal notes (only admins see these)</label>
          <textarea class="us-in" id="uNotes" name="notes" maxlength="500"><?= $v('notes') ?></textarea>
        </div>
      </fieldset>

      <div class="us-actions">
        <button class="btn btn--primary" type="submit"><?= $editing ? 'Save changes' : 'Add user' ?></button>
        <?php if ($editing): ?><a class="btn btn--ghost" href="<?= h($base) ?>">Cancel</a><?php endif; ?>
      </div>
    </form>

    <div class="us-rules" style="margin-top:14px">
      <span><strong>Admin</strong>: full access to every admin page and the POS.</span>
      <span><strong>Cashier</strong>: uses the POS and sees their own sales.</span>
      <span><strong>Waiter</strong>: uses the POS to take orders and payments.</span>
      <span>At least one active admin must always remain. You cannot deactivate, demote or delete your own account.</span>
    </div>
  </details>
</section>

<!-- LIST -->
<section class="us-card" aria-labelledby="list-title">
  <div class="us-head">
    <div>
      <p class="eyebrow">Staff</p>
      <h2 class="us-title" id="list-title">All users</h2>
    </div>
    <span class="us-muted"><?= count($users) ?> shown</span>
  </div>

  <form method="get" action="<?= h($base) ?>" class="us-filter">
    <div class="us-field">
      <label for="fq">Search</label>
      <input class="us-in" id="fq" type="search" name="q" value="<?= h($q) ?>" placeholder="Name, username, phone, email or ID">
    </div>
    <div class="us-field">
      <label for="fr">Role</label>
      <select class="us-in" id="fr" name="role">
        <option value="">All roles</option>
        <?php foreach (US_ROLES as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $role === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="us-field">
      <label for="fs">Access</label>
      <select class="us-in" id="fs" name="status">
        <option value="">All</option>
        <option value="active"   <?= $status === 'active' ? 'selected' : '' ?>>Can sign in</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Deactivated</option>
        <option value="locked"   <?= $status === 'locked' ? 'selected' : '' ?>>Locked out</option>
      </select>
    </div>
    <div class="us-actions">
      <button class="btn btn--ghost" type="submit">Apply</button>
      <?php if ($q !== '' || $role !== '' || $status !== ''): ?><a class="btn btn--ghost" href="<?= h($base) ?>">Clear</a><?php endif; ?>
    </div>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Person</th><th>Contact</th><th>Role</th><th>Access</th>
          <th>Last sign-in</th><th class="r">Sales</th><th class="r">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$users): ?>
          <tr><td colspan="7" class="empty">No users match these filters.</td></tr>
        <?php endif; ?>

        <?php foreach ($users as $u):
            $uid      = (int) $u['id'];
            $isMe     = $uid === $me;
            $locked   = !empty($u['locked_until']) && strtotime((string) $u['locked_until']) > time();
            $initials = us_initials((string) $u['first_name'], (string) $u['last_name'], (string) $u['name']);
            $fullName = trim((string) $u['first_name'] . ' ' . (string) $u['last_name']) ?: (string) $u['name'];
        ?>
          <tr>
            <td>
              <div class="us-person">
                <span class="us-avatar" aria-hidden="true"><?= h($initials) ?></span>
                <div style="min-width:0">
                  <div class="us-name"><?= h($fullName) ?><?= $isMe ? ' <span class="us-muted">(you)</span>' : '' ?></div>
                  <div class="us-login">@<?= h($u['login']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <div><?= $u['phone'] ? h($u['phone']) : '<span class="us-muted">No phone</span>' ?></div>
              <div class="us-muted"><?= $u['email'] ? h($u['email']) : '' ?></div>
            </td>
            <td><span class="us-role us-role--<?= h($u['role']) ?>"><?= h(US_ROLES[$u['role']] ?? ucfirst((string) $u['role'])) ?></span></td>
            <td>
              <?php if ($locked): ?>
                <span class="badge badge--bad">Locked</span>
              <?php elseif ((int) $u['active']): ?>
                <span class="badge badge--ok">Can sign in</span>
              <?php else: ?>
                <span class="badge badge--muted">Deactivated</span>
              <?php endif; ?>
              <?php if ((int) $u['failed_logins'] > 0): ?>
                <div class="us-muted"><?= (int) $u['failed_logins'] ?> failed attempt(s)</div>
              <?php endif; ?>
            </td>
            <td class="us-muted num"><?= $u['last_login_at'] ? h(date('j M Y, H:i', strtotime($u['last_login_at']))) : 'Never' ?></td>
            <td class="r num"><?= (int) $u['orders_n'] ?></td>
            <td class="r">
              <div class="us-actions" style="justify-content:flex-end">
                <a class="btn btn--ghost us-sm" href="<?= h($listUrl(['edit' => $uid])) ?>#user-form">Edit</a>

                <?php if ($locked): ?>
                  <form method="post" action="<?= h($base) ?>" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="unlock">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <button class="btn btn--ghost us-sm" type="submit">Unlock</button>
                  </form>
                <?php endif; ?>

                <?php if (!$isMe): ?>
                  <form method="post" action="<?= h($base) ?>" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <button class="btn btn--ghost us-sm" type="submit"><?= (int) $u['active'] ? 'Deactivate' : 'Reactivate' ?></button>
                  </form>
                <?php endif; ?>

                <details style="display:inline-block;position:relative">
                  <summary class="btn btn--ghost us-sm" style="list-style:none">Reset PIN</summary>
                  <form method="post" action="<?= h($base) ?>" autocomplete="off" class="us-pop">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <label class="us-muted" for="rp<?= $uid ?>">New PIN for @<?= h($u['login']) ?></label>
                    <input class="us-in" id="rp<?= $uid ?>" type="password" name="pin" inputmode="numeric" pattern="[0-9]{4,6}" required autocomplete="new-password" placeholder="4 to 6 digits">
                    <button class="btn btn--primary us-sm" type="submit">Set PIN</button>
                  </form>
                </details>

                <?php if (!$isMe): ?>
                  <form method="post" action="<?= h($base) ?>" style="display:inline"
                        data-confirm="Delete <?= h($fullName) ?>? Users with sales history are deactivated instead.">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <button class="btn btn--danger us-sm" type="submit">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="us-muted">Users with sales or payment history cannot be deleted, because that would break reports. They are deactivated instead and keep their records.</p>
</section>

<?php require APP_ROOT . '/app/shared/views/admin/footer.php'; ?>