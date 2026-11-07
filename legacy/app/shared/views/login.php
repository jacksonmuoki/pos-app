<?php
declare(strict_types=1);
ini_set('display_errors', '1');
$error    = $error ?? null;
$shopName = $shopName ?? 'POS';
$initial  = strtoupper(mb_substr($shopName, 0, 1));

// Direct image URL (hotlinked from Unsplash). Replace with any image address you like.
$bgImage = 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=1600&q=80';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#14532d">
    <title>Sign In | <?= htmlspecialchars($shopName) ?></title>
    <style>
        :root {
            --green-950: #022c22;
            --green-900: #14532d;
            --green-800: #166534;
            --green-700: #15803d;
            --green-600: #16a34a;
            --green-500: #22c55e;
            --green-100: #dcfce7;
            --green-50:  #f0fdf4;
            --ink:       #052e16;
            --muted:     #4b5d52;
            --line:      #d1fae5;
            --danger-bg: #fef2f2;
            --danger-fg: #991b1b;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--ink);
            background: var(--green-50);
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: 100%;
        }

        .layout {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
        }

        /* ---------- Photo panel ---------- */
        .visual {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: clamp(1.5rem, 4vw, 3.5rem);
            color: #fff;
            overflow: hidden;
            background: linear-gradient(160deg, var(--green-700), var(--green-950));
        }

        .visual-photo {
            position: absolute;
            inset: 0;
            background: url('<?= htmlspecialchars($bgImage) ?>') center / cover no-repeat;
            transform: scale(1.02);
        }

        .visual::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(2, 44, 34, 0.94) 0%, rgba(2, 44, 34, 0.4) 55%, rgba(2, 44, 34, 0.2) 100%);
        }

        .visual-content { position: relative; z-index: 1; max-width: 32rem; }

        .eyebrow {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--green-100);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            margin-bottom: 1rem;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .visual h2 {
            font-size: clamp(1.8rem, 3.2vw, 2.8rem);
            font-weight: 700;
            line-height: 1.12;
            letter-spacing: -0.02em;
        }

        .visual p {
            margin-top: 0.75rem;
            font-size: clamp(0.95rem, 1.2vw, 1.1rem);
            color: var(--green-100);
            opacity: 0.88;
            line-height: 1.55;
            max-width: 26rem;
        }

        /* ---------- Sign-in panel ---------- */
        .panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.25rem, 4vw, 3rem) clamp(1rem, 4vw, 2.5rem);
            background: var(--green-50);
        }

        .card {
            width: 100%;
            max-width: 370px;
            text-align: center;
        }

        .brand {
            width: 58px;
            height: 58px;
            margin: 0 auto 1.1rem;
            border-radius: 18px;
            background: linear-gradient(145deg, var(--green-600), var(--green-900));
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            box-shadow: 0 12px 24px -10px rgba(21, 128, 61, 0.6);
        }

        .card h1 {
            font-size: clamp(1.3rem, 2.4vw, 1.65rem);
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .subtitle {
            font-size: 0.95rem;
            color: var(--muted);
            margin: 0.4rem 0 1.5rem;
        }

        .error {
            background: var(--danger-bg);
            border: 1px solid #fecaca;
            color: var(--danger-fg);
            padding: 0.7rem 0.9rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
        }

        /* PIN field (styled slots) */
        .pin-field {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(0.45rem, 2vw, 0.75rem);
            padding: 0.9rem 1rem;
            margin: 0 auto clamp(1.25rem, 3.5vh, 1.75rem);
            max-width: 320px;
            background: #fff;
            border: 1.5px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(5, 46, 22, 0.05), 0 8px 20px -14px rgba(5, 46, 22, 0.25);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .pin-field.active {
            border-color: var(--green-500);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.18);
        }

        .slot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #d1d5db;
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .slot.on {
            background: var(--green-600);
            transform: scale(1.15);
        }

              

        /* Keypad */
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: clamp(0.6rem, 2vw, 0.85rem);
            max-width: 320px;
            margin: 0 auto;
        }

        .key {
            height: clamp(54px, 9vh, 72px);
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fff;
            font: inherit;
            font-size: clamp(1.25rem, 2.6vw, 1.5rem);
            font-weight: 600;
            color: var(--ink);
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(5, 46, 22, 0.06);
            transition: background 0.12s ease, transform 0.08s ease;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            user-select: none;
        }

        .key:hover { background: var(--green-100); }
        .key:active { transform: scale(0.95); background: #bbf7d0; }
        .key:focus-visible { outline: 3px solid var(--green-500); outline-offset: 2px; }
        .key:disabled { opacity: 0.5; cursor: not-allowed; }

        .key.action { color: var(--green-800); font-size: 1.15rem; }

        .key.enter {
            background: linear-gradient(145deg, var(--green-600), var(--green-800));
            border-color: transparent;
            color: #fff;
            box-shadow: 0 6px 16px -6px rgba(21, 128, 61, 0.7);
        }
        .key.enter:hover { background: linear-gradient(145deg, var(--green-700), var(--green-900)); }

        .footer-note {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .card.shake { animation: shake 0.4s ease; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }

        /* Tablet portrait and phones: photo becomes a banner */
        @media (max-width: 860px) {
            .layout {
                grid-template-columns: 1fr;
                grid-template-rows: minmax(160px, 32vh) 1fr;
            }
            .visual { padding: 1.5rem; min-height: 160px; }
            .visual .eyebrow,
            .visual p { display: none; }
            .visual h2 { font-size: clamp(1.4rem, 5vw, 1.9rem); }
            .panel {
                align-items: flex-start;
                padding-top: 1.75rem;
                padding-bottom: calc(1.75rem + env(safe-area-inset-bottom, 0px));
            }
        }

        @media (max-width: 480px) {
            .card { max-width: 100%; }
            .brand { width: 52px; height: 52px; font-size: 1.3rem; border-radius: 15px; }
            .key { height: 58px; border-radius: 14px; }
            .footer-note { display: none; }
        }

        /* Phones in landscape */
        @media (max-height: 560px) and (orientation: landscape) {
            .layout { grid-template-columns: 1fr 1fr; grid-template-rows: none; }
            .visual { padding: 1.25rem; }
            .visual h2 { font-size: 1.3rem; }
            .panel { padding: 1rem; align-items: center; }
            .brand, .footer-note { display: none; }
            .subtitle { margin-bottom: 0.8rem; }
            .pin-field { margin-bottom: 0.8rem; padding: 0.6rem; }
            .key { height: 46px; }
            .keypad { gap: 0.45rem; }
        }

        @media (max-width: 340px) {
            .key { height: 50px; font-size: 1.1rem; }
            .keypad { gap: 0.45rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="layout">

    <section class="visual" aria-hidden="true">
        <div class="visual-photo"></div>
        <div class="visual-content">
            <span class="eyebrow"><?= htmlspecialchars($shopName) ?></span>
            <h2>Serve faster.<br>Sell smarter.</h2>
            <p>Sign in with your PIN to start taking orders and managing sales.</p>
        </div>
    </section>

    <section class="panel">
        <main class="card" id="card">
            <div class="brand" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
            <h1>Welcome back</h1>
            <p class="subtitle">Enter your PIN to continue</p>

            <?php if ($error): ?>
                <div class="error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="pin-field" id="pinField" aria-live="polite" aria-label="PIN entered">
                <span class="slot"></span><span class="slot"></span><span class="slot"></span>
                <span class="slot"></span><span class="slot"></span><span class="slot"></span>
            </div>

            <div class="keypad">
                <?php foreach (['1','2','3','4','5','6','7','8','9'] as $n): ?>
                    <button type="button" class="key" data-key="<?= $n ?>"><?= $n ?></button>
                <?php endforeach; ?>
                <button type="button" class="key action" data-action="back" aria-label="Delete last digit">&#9003;</button>
                <button type="button" class="key" data-key="0">0</button>
                <button type="button" class="key action enter" data-action="enter" aria-label="Submit PIN">&#10003;</button>
            </div>

            <p class="footer-note">Forgot your PIN? Ask an administrator.</p>

            <form id="loginForm" method="POST" action="<?= BASE_URL ?>login">
                <input type="hidden" name="pin" id="pinInput">
            </form>
        </main>
    </section>

</div>

<script>
(function () {
    const MIN = 4;
    const MAX = 6;
    let pin = '';
    let submitting = false;

    const card   = document.getElementById('card');
    const field  = document.getElementById('pinField');
    const slots  = document.querySelectorAll('.slot');
    const form   = document.getElementById('loginForm');
    const input  = document.getElementById('pinInput');
    const keys   = document.querySelectorAll('.key');

    function render() {
        slots.forEach((s, i) => s.classList.toggle('on', i < pin.length));
        field.classList.toggle('active', pin.length > 0);
    }

    function shake() {
        card.classList.remove('shake');
        void card.offsetWidth;
        card.classList.add('shake');
    }

    function submit() {
        if (submitting) return;
        if (pin.length < MIN) { shake(); return; }
        submitting = true;
        keys.forEach(b => b.disabled = true);
        input.value = pin;
        form.submit();
    }

    function press(digit) {
        if (submitting || pin.length >= MAX) return;
        pin += digit;
        render();
        if (pin.length === MAX) submit();
    }

    function back() {
        if (submitting) return;
        pin = pin.slice(0, -1);
        render();
    }

    keys.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.dataset.key !== undefined) press(btn.dataset.key);
            else if (btn.dataset.action === 'back') back();
            else if (btn.dataset.action === 'enter') submit();
        });
    });

    document.addEventListener('keydown', e => {
        if (/^\d$/.test(e.key)) press(e.key);
        else if (e.key === 'Backspace') back();
        else if (e.key === 'Enter') submit();
    });

    <?php if ($error): ?>
    shake();
    <?php endif; ?>
})();
</script>

</body>
</html>