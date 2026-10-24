@extends('layouts.app')

@section('title', 'Edit ' . $user->name)

@section('content')
<section class="hero">
    <div>
        <div class="eyebrow">Team</div>
        <h1>{{ $user->name }}</h1>
        <p>{{ $user->email }} · {{ $user->roleLabel() }}</p>
    </div>
    <div class="hero-actions"><a class="btn" href="{{ route('team.index') }}">← Back to team</a></div>
</section>

<div class="grid-two">
    <form class="card" method="POST" action="{{ route('team.update', $user->id) }}">
        @csrf @method('PATCH')
        <div class="card-head"><h3>Account details</h3></div>
        <div class="card-body">
            <div class="grid-even">
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $errors->has('name') ? 'err' : '' }}" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $errors->has('email') ? 'err' : '' }}" required>
                </div>
                <div class="field">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" class="{{ $errors->has('role_id') ? 'err' : '' }}" required>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" @selected((string) old('role_id', $user->role_id) === (string) $r->id)>{{ $r->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="password">New password</label>
                    <input id="password" type="password" name="password" minlength="8" autocomplete="new-password">
                    <span class="hint">Leave empty to keep the current password.</span>
                </div>
            </div>
            <div class="field">
                <input type="hidden" name="active" value="0">
                <label class="check"><input type="checkbox" name="active" value="1" @checked(old('active', $user->active ? '1' : '0') == '1')> Account is active and can sign in</label>
                @if ($user->id === $me->id)<span class="hint">You cannot disable your own account.</span>@endif
            </div>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
    </form>

    <div>
        <section class="card">
            <div class="card-head"><h3>Reset password</h3></div>
            <form method="POST" action="{{ route('team.reset', $user->id) }}" class="card-body" onsubmit="return confirm('Generate a new password for {{ $user->email }}?')">
                @csrf
                <p class="notice-text" style="margin:0 0 14px">Creates a random password and shows it once. The person is signed out everywhere.</p>
                <button class="btn" type="submit">Generate new password</button>
            </form>
        </section>

        @if ($user->id !== $me->id)
        <section class="card danger">
            <div class="card-head"><h3>Delete account</h3></div>
            <form method="POST" action="{{ route('team.destroy', $user->id) }}" class="card-body" onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <p class="notice-text" style="margin:0 0 14px">People with sales on record cannot be deleted. Disable their account instead.</p>
                <button class="btn btn-danger" type="submit">Delete account</button>
            </form>
        </section>
        @endif
    </div>
</div>
@endsection