<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title>{{ ucfirst(tenant('id')) }} POS — Staff Login</title>

    <style>
        :root {
            --ink-900: #0f172a;
            --ink-800: #1e293b;
            --ink-700: #334155;
            --ink-600: #475569;
            --ink-500: #64748b;
            --ink-400: #94a3b8;
            --ink-300: #cbd5e1;
            --ink-200: #e2e8f0;
            --ink-100: #f1f5f9;
            --ink-50:  #f8fafc;

            --accent-700: #047857;
            --accent-600: #059669;
            --accent-500: #10b981;
            --accent-50:  #ecfdf5;

            --danger-700: #b91c1c;
            --danger-600: #dc2626;
            --danger-50:  #fef2f2;
            --danger-200: #fecaca;

            --radius: 10px;
            --shadow-btn: 0 1px 2px rgba(15,23,42,.05), 0 2px 6px -2px rgba(4,120,87,.35);

            --safe-top:    env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left:   env(safe-area-inset-left, 0px);
            --safe-right:  env(safe-area-inset-right, 0px);
        }

        *, *::before, *::after { box-sizing: border-box; }

        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            height: 100%;
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--ink-900);
            background: var(--ink-50);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            -webkit-tap-highlight-color: transparent;
        }

        /* ============================================================
           FULL-PAGE SHELL
           ============================================================ */
        .pos-shell {
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
            padding-left: var(--safe-left);
            padding-right: var(--safe-right);
        }

        /* ---------- Left brand panel (desktop/tablet only) ---------- */
        .brand-panel {
            display: none;
            position: relative;
            overflow: hidden;
            background: var(--ink-900);
            color: #f1f5f9;
            padding: clamp(32px, 4vw, 64px);
            flex-direction: column;
            justify-content: space-between;
            gap: 32px;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(900px 500px at 100% 0%, rgba(5,150,105,.18), transparent 60%),
                radial-gradient(700px 400px at 0% 100%, rgba(16,185,129,.10), transparent 60%);
            pointer-events: none;
        }

        .brand-top,
        .brand-mid,
        .brand-bottom {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: -.01em;
            color: #ffffff;
        }

        .brand-logo-mark {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: var(--accent-600);
            font-size: 17px;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
        }

        .brand-mid {
            max-width: 460px;
        }

        .brand-mid h2 {
            margin: 0 0 16px;
            font-size: clamp(26px, 2.8vw, 40px);
            line-height: 1.15;
            font-weight: 700;
            letter-spacing: -.03em;
            color: #ffffff;
        }

        .brand-mid p {
            margin: 0;
            font-size: clamp(14px, 1.1vw, 15px);
            line-height: 1.6;
            color: rgba(226,232,240,.65);
        }

        .brand-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: clamp(24px, 3vw, 36px);
        }

        .brand-feature {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: clamp(13px, 1vw, 14px);
            line-height: 1.5;
            color: rgba(226,232,240,.75);
        }

        .brand-feature svg {
            flex-shrink: 0;
            width: 18px;
            height: 18px;
            margin-top: 1px;
            color: var(--accent-500);
        }

        .brand-bottom {
            font-size: 12px;
            color: rgba(226,232,240,.40);
        }

        /* ============================================================
           RIGHT LOGIN PANEL — full height, centered content
           ============================================================ */
        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(24px, 4vw, 48px) clamp(20px, 4vw, 48px);
            background: #ffffff;
            min-height: 100%;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        /* ---------- Mobile brand bar (top of login panel) ---------- */
        .mobile-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: clamp(28px, 5vw, 40px);
        }

        .mobile-brand .brand-logo-mark {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            font-size: 18px;
        }

        .mobile-brand-text {
            font-size: 16px;
            font-weight: 600;
            color: var(--ink-800);
            letter-spacing: -.01em;
        }

        /* ---------- Headings (centered) ---------- */
        .login-heading {
            margin: 0 0 8px;
            font-size: clamp(22px, 3vw, 28px);
            font-weight: 700;
            letter-spacing: -.025em;
            color: var(--ink-900);
            text-align: center;
        }

        .login-subheading {
            margin: 0 auto clamp(28px, 4vw, 36px);
            max-width: 320px;
            font-size: clamp(13px, 1.4vw, 15px);
            line-height: 1.55;
            color: var(--ink-500);
            text-align: center;
        }

        /* ---------- Error (centered) ---------- */
        .error {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid var(--danger-200);
            border-radius: var(--radius);
            background: var(--danger-50);
            color: var(--danger-700);
            font-size: 13px;
            line-height: 1.45;
            text-align: center;
        }

        .error svg {
            flex-shrink: 0;
            width: 15px;
            height: 15px;
            color: var(--danger-600);
        }

        /* ---------- Form ---------- */
        .field {
            margin-bottom: 20px;
        }

        .field-label {
            display: block;
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-700);
            text-align: center;
        }

        .pin-input {
            width: 100%;
            height: clamp(58px, 7vw, 64px);
            padding: 0 16px;
            border: 1.5px solid var(--ink-300);
            border-radius: var(--radius);
            outline: none;
            background: var(--ink-50);
            color: var(--ink-900);
            font-size: clamp(22px, 3vw, 26px);
            font-weight: 600;
            letter-spacing: 0.5em;
            text-align: center;
            text-indent: 0.5em;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
            -webkit-appearance: none;
            appearance: none;
        }

        .pin-input::placeholder {
            color: var(--ink-300);
            letter-spacing: 0.5em;
            text-indent: 0.5em;
            font-weight: 400;
        }

        .pin-input:hover {
            border-color: var(--ink-400);
        }

        .pin-input:focus {
            background: #ffffff;
            border-color: var(--accent-600);
            box-shadow: 0 0 0 4px rgba(5,150,105,.13);
        }

        .pin-input.is-invalid {
            border-color: var(--danger-600);
            background: var(--danger-50);
        }

        .pin-input.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(220,38,38,.12);
        }

        /* ---------- Button ---------- */
        .login-button {
            width: 100%;
            height: clamp(52px, 6vw, 56px);
            border: 0;
            border-radius: var(--radius);
            background: var(--accent-700);
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -.005em;
            cursor: pointer;
            box-shadow: var(--shadow-btn);
            transition: background .15s ease, box-shadow .15s ease, transform .1s ease;
            touch-action: manipulation;
        }

        .login-button:hover {
            background: #065f46;
        }

        .login-button:active {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(15,23,42,.1);
        }

        .login-button:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(5,150,105,.28), var(--shadow-btn);
        }

        .login-button:disabled {
            opacity: .8;
            cursor: not-allowed;
        }

        .login-button-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .spinner {
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255,255,255,.35);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }

        .login-button.is-loading .spinner { display: inline-block; }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ---------- Hint (centered) ---------- */
        .hint {
            margin: 14px auto 0;
            max-width: 320px;
            font-size: 12px;
            line-height: 1.5;
            color: var(--ink-400);
            text-align: center;
        }

        /* ---------- Footer ---------- */
        .login-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: clamp(28px, 4vw, 40px);
            font-size: 12px;
            color: var(--ink-400);
            text-align: center;
        }

        .login-footer svg {
            width: 12px;
            height: 12px;
            color: var(--accent-600);
            flex-shrink: 0;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */

        /* ---- MOBILE (< 960px): single full-page column ---- */
        @media (max-width: 959px) {
            .pos-shell {
                grid-template-rows: 1fr;
                background: #ffffff;
            }

            .login-panel {
                background: #ffffff;
                padding: calc(28px + var(--safe-top)) clamp(20px, 5vw, 40px)
                         calc(28px + var(--safe-bottom));
                min-height: 100dvh;
            }

            .brand-panel { display: none; }
        }

        /* ---- DESKTOP/TABLET LANDSCAPE (>= 960px): split full-page ---- */
        @media (min-width: 960px) {
            .pos-shell {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr;
            }

            .brand-panel { display: flex; }

            .mobile-brand { display: none; }

            .login-panel {
                background: #ffffff;
            }
        }

        /* ---- Large desktop ---- */
        @media (min-width: 1280px) {
            .pos-shell {
                grid-template-columns: 1.05fr 1fr;
            }
            .login-form-wrap { max-width: 420px; }
        }

        /* ---- Ultrawide POS terminals ---- */
        @media (min-width: 1920px) {
            .brand-panel { padding: 96px 120px; }
            .brand-mid { max-width: 560px; }
            .brand-mid h2 { font-size: 44px; }
            .login-form-wrap { max-width: 440px; }
        }

        /* ---- Small phones (< 380px) ---- */
        @media (max-width: 379px) {
            .login-panel {
                padding-left: 18px;
                padding-right: 18px;
            }
            .pin-input {
                height: 56px;
                font-size: 20px;
                letter-spacing: 0.4em;
                text-indent: 0.4em;
            }
            .login-button { height: 50px; font-size: 14px; }
            .mobile-brand { margin-bottom: 24px; }
            .mobile-brand .brand-logo-mark { width: 40px; height: 40px; font-size: 16px; }
            .mobile-brand-text { font-size: 15px; }
        }

        /* ---- Landscape phones (short viewport) ---- */
        @media (max-height: 540px) and (orientation: landscape) {
            .pos-shell {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr;
            }

            .brand-panel { display: none; }

            .login-panel {
                align-items: flex-start;
                padding-top: calc(16px + var(--safe-top));
                padding-bottom: calc(16px + var(--safe-bottom));
            }

            .login-form-wrap { max-width: 480px; margin: 0 auto; }

            .mobile-brand {
                margin-bottom: 14px;
                gap: 8px;
            }
            .mobile-brand .brand-logo-mark { width: 32px; height: 32px; font-size: 14px; border-radius: 7px; }
            .mobile-brand-text { font-size: 14px; }

            .login-heading { font-size: 18px; margin-bottom: 4px; }
            .login-subheading { margin-bottom: 14px; font-size: 12px; }
            .field { margin-bottom: 12px; }
            .field-label { margin-bottom: 6px; font-size: 12px; }
            .pin-input { height: 46px; font-size: 18px; letter-spacing: 0.35em; text-indent: 0.35em; }
            .login-button { height: 44px; }
            .hint { margin-top: 8px; font-size: 11px; }
            .login-footer { margin-top: 14px; font-size: 11px; }
        }

        /* ---- Foldable / very narrow tall ---- */
        @media (max-width: 340px) and (min-height: 600px) {
            .pin-input {
                font-size: 19px;
                letter-spacing: 0.35em;
                text-indent: 0.35em;
            }
        }

        /* ---- Reduced motion ---- */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

<div class="pos-shell">

    <!-- Left brand panel (desktop only) -->
    <aside class="brand-panel" aria-hidden="true">
        <div class="brand-top">
            <div class="brand-logo">
                <span class="brand-logo-mark">P</span>
                <span>{{ ucfirst(tenant('id')) }} POS</span>
            </div>
        </div>

        <div class="brand-mid">
            <h2>Staff sign in to your point of sale.</h2>
            <p>Enter your PIN to start a shift, open the register, and process orders instantly.</p>

            <div class="brand-features">
                <div class="brand-feature">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    <span>PIN-based sign in for speed at the counter</span>
                </div>
                <div class="brand-feature">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    <span>Role-aware access tied to your staff account</span>
                </div>
                <div class="brand-feature">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    <span>Audit-logged sessions for every transaction</span>
                </div>
            </div>
        </div>

        <div class="brand-bottom">
            &copy; {{ date('Y') }} {{ ucfirst(tenant('id')) }} POS. All rights reserved.
        </div>
    </aside>

    <!-- Full-height login panel -->
    <main class="login-panel">
        <div class="login-form-wrap">

            <!-- Mobile brand (hidden on desktop) -->
            <div class="mobile-brand">
                <span class="brand-logo-mark">P</span>
                <span class="mobile-brand-text">{{ ucfirst(tenant('id')) }} POS</span>
            </div>

            <h1 class="login-heading">Staff sign in</h1>
            <p class="login-subheading">Enter your staff PIN to access the register.</p>

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="error" role="alert">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="field">
                    <label for="password" class="field-label">Staff PIN / Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="pin-input {{ $errors->any() ? 'is-invalid' : '' }}"
                        placeholder="••••••"
                        inputmode="numeric"
                        pattern="[0-9]{4,8}"
                        minlength="4"
                        maxlength="8"
                        autocomplete="current-password"
                        aria-describedby="pin-hint"
                        title="Enter 4 to 8 digits"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <span class="login-button-inner">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="btn-label">Sign In</span>
                    </span>
                </button>

                <p class="hint" id="pin-hint">
                    Login submits automatically at 6 or 8 digits.
                </p>
            </form>

            <div class="login-footer">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                <span>Secure staff access</span>
            </div>

        </div>
    </main>

</div>

<script>
    (function () {
        const input  = document.getElementById('password');
        const form   = document.getElementById('loginForm');
        const button = document.getElementById('loginButton');

        let submitted = false;

        function setLoading(isLoading) {
            submitted = isLoading;
            button.disabled = isLoading;
            button.classList.toggle('is-loading', isLoading);
            button.querySelector('.btn-label').textContent = isLoading ? 'Signing in…' : 'Sign In';
        }

        input.addEventListener('input', function () {
            const cleaned = this.value.replace(/\D/g, '').slice(0, 8);
            if (cleaned !== this.value) this.value = cleaned;

            input.classList.remove('is-invalid');

            if (!submitted && (cleaned.length === 8 || cleaned.length === 8)) {
                setLoading(true);
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', function (e) {
            const len = input.value.length;
            if (len < 4 || len > 8) {
                e.preventDefault();
                input.classList.add('is-invalid');
                input.focus();
                return;
            }
            setLoading(true);
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const digits = text.replace(/\D/g, '').slice(0, 8);
            this.value = digits;
            this.dispatchEvent(new Event('input'));
        });

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                if (document.activeElement === input) {
                    input.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            });
        }
    })();
</script>

</body>
</html>