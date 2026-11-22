@extends('super.layout')

@section('title', 'Sign In')

@section('content')

<div class="simple-login">
    <div class="login-panel">

        <div class="login-heading">
            <h2>Sign in</h2>
            <p>Access your account to continue.</p>
        </div>

        @if(session('status'))
            <div class="login-message success">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="login-message error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('super.login') }}" id="loginForm">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="login-options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" id="loginBtn">
                <span class="btn-label">Continue</span>
                <span class="btn-spinner"></span>
            </button>
        </form>

        <div class="login-footer">
            Authorized access only
        </div>

    </div>
</div>

@endsection

@push('styles')
<style>
body:has(.simple-login) {
    background: #f5f6f7 !important;
}

body:has(.simple-login) .sidebar,
body:has(.simple-login) .topbar,
body:has(.simple-login) .overlay {
    display: none !important;
}

body:has(.simple-login) .main-area {
    margin-left: 0 !important;
    min-height: 100vh;
}

body:has(.simple-login) .content {
    padding: 0 !important;
    margin: 0 !important;
    min-height: 100vh;
}

.simple-login,
.simple-login * {
    box-sizing: border-box;
}

.simple-login {
    width: 100%;
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #f5f6f7;
    font-family: 'DM Sans', sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.login-panel {
    width: 100%;
    max-width: 360px;
    padding: 26px 24px 20px;
    background: #ffffff;
    border: 1px solid #e4e7eb;
    border-radius: 8px;
    box-shadow:
        0 1px 2px rgba(16, 24, 40, 0.04),
        0 6px 18px rgba(16, 24, 40, 0.05);
}

.login-heading {
    text-align: center;
    margin-bottom: 20px;
}

.login-heading h2 {
    margin: 0 0 4px;
    color: #101828;
    font-size: 19px;
    font-weight: 700;
    letter-spacing: -0.3px;
}

.login-heading p {
    margin: 0;
    color: #667085;
    font-size: 12px;
    line-height: 1.5;
}

.form-group {
    margin-bottom: 14px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    color: #344054;
    font-size: 12px;
    font-weight: 600;
}

.form-group input {
    display: block;
    width: 100%;
    height: 42px;
    padding: 0 12px;
    border: 1px solid #d0d5dd;
    border-radius: 6px;
    background: #ffffff;
    color: #101828;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}

.form-group input:hover {
    border-color: #98a2b3;
}

.form-group input:focus {
    border-color: #344054;
    box-shadow: 0 0 0 3px rgba(52, 64, 84, 0.10);
}

.login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 2px 0 16px;
    font-size: 12px;
}

.remember {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #475467;
    cursor: pointer;
    user-select: none;
}

.remember input {
    width: 14px;
    height: 14px;
    accent-color: #344054;
    cursor: pointer;
}

.forgot {
    color: #344054;
    text-decoration: none;
    font-weight: 600;
}

.forgot:hover {
    text-decoration: underline;
}

.login-panel button[type="submit"] {
    position: relative;
    width: 100%;
    height: 42px;
    border: none;
    border-radius: 6px;
    background: #101828;
    color: #ffffff;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}

.login-panel button[type="submit"]:hover:not(:disabled) {
    background: #1d2939;
}

.login-panel button[type="submit"]:focus-visible {
    outline: 3px solid rgba(16, 24, 40, 0.20);
    outline-offset: 3px;
}

.login-panel button[type="submit"]:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.btn-spinner {
    display: none;
    width: 15px;
    height: 15px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto;
}

.login-panel button.is-loading .btn-label { display: none; }
.login-panel button.is-loading .btn-spinner { display: inline-block; }

@keyframes spin {
    to { transform: rotate(360deg); }
}

.login-footer {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #f0f1f3;
    text-align: center;
    color: #98a2b3;
    font-size: 11px;
}

.login-message {
    padding: 9px 12px;
    margin-bottom: 14px;
    border-radius: 6px;
    font-size: 12px;
    text-align: center;
    line-height: 1.5;
}

.login-message.error {
    background: #fef3f2;
    color: #b42318;
    border: 1px solid #fecdca;
}

.login-message.success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

@media (max-width: 480px) {
    .simple-login {
        padding: 16px;
    }

    .login-panel {
        max-width: 100%;
        padding: 24px 20px 18px;
    }

    .login-options {
        flex-wrap: wrap;
        gap: 8px;
    }
}

@media (max-width: 320px) {
    .login-panel {
        padding: 20px 16px;
    }

    .form-group input,
    .login-panel button {
        height: 40px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.001ms !important;
        transition-duration: 0.001ms !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('loginBtn');

    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.classList.add('is-loading');
        });
    }
})();
</script>
@endpush