<?php
/*
 * Admin layout footer. Closes <main> and the layout opened in header.php.
 * Optional: set $pageRefresh (seconds) before the header to auto-refresh.
 */
$shopNameFooter = (string) setting('shop_name', 'POS');
$footUser       = (string) (($_SESSION['user']['name'] ?? '') ?: 'Admin');
?>
    </main>

    <footer class="footer">
      <span>© <?= date('Y') ?> <?= h($shopNameFooter) ?>. All rights reserved.</span>
      <span>Signed in as <?= h($footUser) ?> · <?= h(date('j M Y, H:i')) ?></span>
    </footer>

  </div><!-- /.main -->
</div><!-- /.shell -->

<script>
(function () {
  'use strict';
  var root = document.documentElement;
  var body = document.body;

  /* ---------- Theme ---------- */
  var themeBtn = document.getElementById('themeToggle');
  function syncTheme() {
    var dark = root.getAttribute('data-theme') === 'dark';
    themeBtn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    themeBtn.querySelector('[data-theme-icon="dark"]').hidden = !dark;
    themeBtn.querySelector('[data-theme-icon="light"]').hidden = dark;
    var meta = document.getElementById('metaTheme');
    if (meta) meta.setAttribute('content', dark ? '#0b0d10' : '#f6f7f9');
  }
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('pos-admin-theme', next); } catch (e) {}
      syncTheme();
    });
    syncTheme();
  }

  /* ---------- Mobile drawer ---------- */
  var menuBtn = document.getElementById('menuToggle');
  var overlay = document.getElementById('menuOverlay');
  function setNav(open) {
    body.classList.toggle('nav-open', open);
    if (menuBtn) menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (menuBtn) menuBtn.addEventListener('click', function () { setNav(!body.classList.contains('nav-open')); });
  if (overlay) overlay.addEventListener('click', function () { setNav(false); });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 1024) setNav(false);
  });

  /* ---------- Dropdowns (notifications, user) ---------- */
  var dropdowns = document.querySelectorAll('.dropdown');
  dropdowns.forEach(function (dd) {
    var trigger = dd.querySelector('button');
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = !dd.classList.contains('is-open');
      dropdowns.forEach(function (o) { o.classList.remove('is-open'); });
      dd.classList.toggle('is-open', open);
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (e) {
    dropdowns.forEach(function (dd) {
      if (!dd.contains(e.target)) {
        dd.classList.remove('is-open');
        var t = dd.querySelector('button');
        if (t) t.setAttribute('aria-expanded', 'false');
      }
    });
  });

  /* ---------- Mobile search overlay ---------- */
  var mBtn = document.getElementById('searchMobileBtn');
  var sInput = document.getElementById('searchInput');
  if (mBtn) {
    mBtn.addEventListener('click', function () {
      body.classList.add('search-open');
      if (sInput) sInput.focus();
    });
  }

  /* ---------- Global search (live, over the admin pages) ---------- */
  var search = document.getElementById('globalSearch');
  var results = document.getElementById('searchResults');
  var index = [
    { label: 'Dashboard',     url: <?= json_encode(BASE_URL . 'admin') ?> },
    { label: 'Point of sale', url: <?= json_encode(BASE_URL . 'cashier') ?> },
    { label: 'Sales',         url: <?= json_encode(legacy_url('admin', 'sales')) ?> },
    { label: 'Reports',       url: <?= json_encode(legacy_url('admin', 'reports')) ?> },
    { label: 'Products',      url: <?= json_encode(legacy_url('admin', 'products')) ?> },
    { label: 'Categories',    url: <?= json_encode(legacy_url('admin', 'categories')) ?> },
    { label: 'Inventory',     url: <?= json_encode(legacy_url('admin', 'inventory')) ?> },
    { label: 'Users',         url: <?= json_encode(legacy_url('admin', 'users')) ?> },
    { label: 'Settings',      url: <?= json_encode(legacy_url('admin', 'settings')) ?> }
  ];
  var active = -1;

  function render(q) {
    q = q.trim().toLowerCase();
    var matches = index.filter(function (i) { return !q || i.label.toLowerCase().indexOf(q) !== -1; });
    results.innerHTML = '';
    if (!matches.length) {
      results.innerHTML = '<div class="search-empty">No pages match “' + q.replace(/[<>&"]/g, '') + '”</div>';
    } else {
      matches.forEach(function (m, idx) {
        var a = document.createElement('a');
        a.className = 'search-item';
        a.href = m.url;
        a.setAttribute('role', 'option');
        a.textContent = m.label;
        if (idx === active) a.classList.add('is-active');
        results.appendChild(a);
      });
    }
    search.classList.add('is-open');
    sInput.setAttribute('aria-expanded', 'true');
  }
  if (sInput) {
    sInput.addEventListener('focus', function () { active = -1; render(sInput.value); });
    sInput.addEventListener('input', function () { active = -1; render(sInput.value); });
    sInput.addEventListener('keydown', function (e) {
      var items = results.querySelectorAll('.search-item');
      if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); render(sInput.value); }
      if (e.key === 'ArrowUp')   { e.preventDefault(); active = Math.max(active - 1, 0); render(sInput.value); }
      if (e.key === 'Enter' && items.length) {
        e.preventDefault();
        (items[active] || items[0]).click();
      }
      if (e.key === 'Escape') { search.classList.remove('is-open'); sInput.blur(); body.classList.remove('search-open'); }
    });
  }
  document.addEventListener('click', function (e) {
    if (search && !search.contains(e.target)) search.classList.remove('is-open');
  });

  /* ---------- Keyboard shortcuts ---------- */
  document.addEventListener('keydown', function (e) {
    var typing = /INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName || '');
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      var input = document.getElementById('searchInput');
      if (input) { input.focus(); input.select(); }
      return;
    }
    if (e.key === 'Escape') {
      setNav(false);
      dropdowns.forEach(function (o) { o.classList.remove('is-open'); });
      body.classList.remove('search-open');
      return;
    }
    if (!typing && e.key === '/') {
      e.preventDefault();
      if (sInput) sInput.focus();
    }
  });

  /* ---------- Confirm dangerous actions: <form data-confirm="..."> ---------- */
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* ---------- Flash auto-dismiss ---------- */
  var flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity .4s';
      flash.style.opacity = '0';
      setTimeout(function () { flash.remove(); }, 400);
    }, 6000);
  }

  /* ---------- Auto-refresh for live pages, paused while the tab is hidden ---------- */
  <?php if (!empty($pageRefresh)): ?>
  setInterval(function () {
    if (!document.hidden && !document.querySelector('form:focus-within')) window.location.reload();
  }, <?= (int) $pageRefresh * 1000 ?>);
  <?php endif; ?>
})();
</script>
</body>
</html>