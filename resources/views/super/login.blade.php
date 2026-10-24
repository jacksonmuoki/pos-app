@extends('super.layout')

@section('title', 'Sign in')
@section('breadcrumb', 'Sign in')

@section('content')

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-head">
            <div class="login-icon">P</div>
            <h2>Super admin sign in</h2>
            <p>Enter your credentials to access the platform.</p>
        </div>

        @error('email')
            <div class="login-error">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('super.login') }}">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="you@company.com"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-btn">Sign in</button>
        </form>

        <div class="login-foot">
            <span>Super administrator access only.</span>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .login-wrapper {
        min-height: calc(100vh - 68px - 64px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 16px;
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-card);
        padding: 34px 32px 30px;
    }

    .login-head {
        text-align: center;
        margin-bottom: 28px;
    }

    .login-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 16px;
        display: grid;
        place-items: center;
        background: var(--nav-bg);
        color: #ffffff;
        border-radius: 12px;
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
        font-size: 20px;
        letter-spacing: -0.5px;
    }

    .login-head h2 {
        font-family: 'Manrope', sans-serif;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.4px;
        margin: 0;
        color: var(--ink);
    }

    .login-head p {
        margin: 8px 0 0;
        font-size: 12.5px;
        color: var(--muted);
        font-weight: 500;
        line-height: 1.5;
    }

    .login-error {
        background: #fdf2e8;
        color: #9b5e2a;
        border: 1px solid #f0dfcf;
        border-radius: var(--radius-sm);
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 18px;
        line-height: 1.5;
    }

    .field {
        margin-bottom: 16px;
    }

    .field label {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 6px;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }

    .field input {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: #fcfdfe;
        color: var(--ink);
        font-size: 13.5px;
        font-weight: 500;
        transition: border 0.15s, box-shadow 0.15s, background 0.15s;
        outline: none;
    }

    .field input::placeholder {
        color: #a6b2c0;
        font-weight: 400;
    }

    .field input:hover {
        border-color: #d3dae2;
    }

    .field input:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    .login-btn {
        width: 100%;
        padding: 12px 16px;
        background: var(--nav-bg);
        color: #ffffff;
        border: 1px solid var(--nav-bg);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.1px;
        cursor: pointer;
        transition: background 0.15s, border 0.15s, transform 0.1s;
        margin-top: 6px;
    }

    .login-btn:hover {
        background: var(--nav-bg-hover);
        border-color: var(--nav-bg-hover);
    }

    .login-btn:active {
        transform: translateY(1px);
    }

    .login-foot {
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid var(--line-soft);
        text-align: center;
    }

    .login-foot span {
        font-size: 11px;
        color: var(--muted);
        font-weight: 500;
        letter-spacing: 0.1px;
    }

    @media (max-width: 480px) {
        .login-card {
            padding: 28px 22px 24px;
        }
        .login-head h2 {
            font-size: 18px;
        }
    }
</style>
@endpush