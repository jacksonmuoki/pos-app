@extends('layouts.app')

@section('title', 'Team')

@section('content')
<section class="hero">
    <div>
        <div class="eyebrow">Administration</div>
        <h1>Team</h1>
        <p>{{ $users->count() }} staff accounts. Each person has one role, and the role decides what they can do.</p>
    </div>
    <div class="hero-actions">
        @if ($me->hasPermission('roles.manage'))<a class="btn" href="{{ route('roles.index') }}">Manage roles</a>@endif
    </div>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Added</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>
                        <div class="cell-main">{{ $u->name }} @if ($u->id === $me->id)<span class="badge info">You</span>@endif</div>
                        <div class="cell-sub">{{ $u->email }}</div>
                    </td>
                    <td><span class="badge muted">{{ $u->roleLabel() }}</span></td>
                    <td>@if ($u->active)<span class="badge ok">Active</span>@else<span class="badge warn">Disabled</span>@endif</td>
                    <td class="cell-sub">{{ $u->created_at?->format('d M Y') }}</td>
                    <td class="num">
                        @if ($me->hasPermission('users.manage'))<a class="btn btn-sm" href="{{ route('team.edit', $u->id) }}">Edit</a>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

@if ($me->hasPermission('users.manage'))
<section class="card">
    <div class="card-head"><h3>Add a staff account</h3><span>They can sign in straight away</span></div>
    <form method="POST" action="{{ route('team.store') }}" class="card-body">
        @csrf
        <div class="grid-even">
            <div class="field">
                <label for="name">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="{{ $errors->has('name') ? 'err' : '' }}" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="{{ $errors->has('email') ? 'err' : '' }}" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" minlength="8" class="{{ $errors->has('password') ? 'err' : '' }}" required autocomplete="new-password">
                <span class="hint">At least 8 characters.</span>
            </div>
            <div class="field">
                <label for="role_id">Role</label>
                <select id="role_id" name="role_id" class="{{ $errors->has('role_id') ? 'err' : '' }}" required>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id }}" @selected((string) old('role_id', $roles->firstWhere('name', 'cashier')?->id) === (string) $r->id)>{{ $r->label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Add staff account</button>
    </form>
</section>
@endif
@endsection