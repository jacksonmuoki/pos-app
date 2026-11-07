<!doctype html>
<html>
<head><meta charset="utf-8"><title>Cashier</title></head>
<body>
<h2>Welcome, <?= htmlspecialchars($user['name']) ?></h2>
<form method="POST" action="<?= BASE_URL ?>cashier/order">
    <button type="submit">New Order</button>
</form>
<a href="<?= BASE_URL ?>logout">Logout</a>
</body>
</html>