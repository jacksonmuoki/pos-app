
@extends('super.layout')

@section('title', 'My Profile')
@section('breadcrumb', 'My Profile')

@section('content')
<div style="max-width:760px;">
    @if(session('success'))
        <div style="padding:14px 18px;margin-bottom:20px;background:#dcfce7;color:#166534;border-radius:10px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:14px 18px;margin-bottom:20px;background:#fee2e2;color:#991b1b;border-radius:10px;">
            <ul style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:28px;">
        <h2 style="margin:0 0 8px;font-size:21px;">Account information</h2>
        <p style="color:#64748b;margin:0 0 25px;">
            Update your Super Admin account details.
        </p>

        <form method="POST" action="{{ route('super.profile.update') }}">
            @csrf
            @method('PATCH')

            <div style="margin-bottom:20px;">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text"
                    value="{{ old('name', $admin->name) }}" required
                    style="display:block;width:100%;padding:12px;margin-top:7px;border:1px solid #cbd5e1;border-radius:8px;">
            </div>

            <div style="margin-bottom:20px;">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email"
                    value="{{ old('email', $admin->email) }}" required
                    style="display:block;width:100%;padding:12px;margin-top:7px;border:1px solid #cbd5e1;border-radius:8px;">
            </div>

            <hr style="border:0;border-top:1px solid #e5e7eb;margin:25px 0;">

            <h3 style="font-size:17px;margin-bottom:8px;">Change password</h3>
            <p style="color:#64748b;font-size:13px;margin-bottom:20px;">
                Leave these fields empty if you do not want to change your password.
            </p>

            <div style="margin-bottom:20px;">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password"
                    autocomplete="current-password"
                    style="display:block;width:100%;padding:12px;margin-top:7px;border:1px solid #cbd5e1;border-radius:8px;">
            </div>

            <div style="margin-bottom:20px;">
                <label for="password">New password</label>
                <input id="password" name="password" type="password"
                    autocomplete="new-password"
                    style="display:block;width:100%;padding:12px;margin-top:7px;border:1px solid #cbd5e1;border-radius:8px;">
            </div>

            <div style="margin-bottom:25px;">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    autocomplete="new-password"
                    style="display:block;width:100%;padding:12px;margin-top:7px;border:1px solid #cbd5e1;border-radius:8px;">
            </div>

            <button type="submit"
                style="background:#0f2a43;color:#fff;border:0;border-radius:8px;padding:12px 22px;font-weight:600;cursor:pointer;">
                Save changes
            </button>
        </form>
    </div>
</div>
@endsection
