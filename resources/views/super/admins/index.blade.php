
@extends('super.layout')

@section('title', 'Super Admins')
@section('breadcrumb', 'Super Admins')

@section('content')
<style>
    .admin-page { max-width: 1200px; margin: auto; }
    .admin-card { background: #fff; border: 1px solid #e5eaf0; border-radius: 14px; padding: 24px; margin-bottom: 22px; }
    .admin-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .admin-heading h2 { margin: 0; font-size: 21px; color: #142b40; }
    .admin-muted { color: #718096; font-size: 13px; margin-top: 5px; }
    .admin-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .admin-field label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 7px; }
    .admin-field input { width: 100%; padding: 11px 12px; border: 1px solid #d7dee8; border-radius: 8px; outline: none; box-sizing: border-box; }
    .admin-field input:focus { border-color: #315d83; box-shadow: 0 0 0 3px #315d8318; }
    .admin-btn { display: inline-flex; justify-content: center; align-items: center; border: 0; border-radius: 8px; padding: 11px 17px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 13px; }
    .admin-btn-primary { background: #12334f; color: #fff; }
    .admin-btn-danger { background: #fff1f2; color: #be123c; }
    .admin-btn-danger:hover { background: #ffe4e6; }
    .admin-table-wrap { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; min-width: 650px; }
    .admin-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #718096; padding: 13px 14px; background: #f8fafc; }
    .admin-table td { padding: 16px 14px; border-top: 1px solid #edf0f4; font-size: 13px; color: #334155; }
    .admin-avatar { display: inline-flex; width: 36px; height: 36px; border-radius: 10px; align-items: center; justify-content: center; background: #e8eef5; color: #12334f; font-weight: 700; margin-right: 10px; }
    .admin-alert { padding: 13px 16px; border-radius: 9px; margin-bottom: 16px; font-size: 13px; }
    .admin-success { background: #ecfdf5; color: #047857; }
    .admin-error { background: #fff1f2; color: #be123c; }
    .admin-search { display: flex; gap: 9px; flex-wrap: wrap; }
    .admin-search input { padding: 10px 12px; border: 1px solid #d7dee8; border-radius: 8px; min-width: 230px; }
    @media(max-width: 750px) { .admin-grid { grid-template-columns: 1fr; } .admin-card { padding: 17px; } }
</style>

<div class="admin-page">

    @if(session('success'))
        <div class="admin-alert admin-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="admin-alert admin-error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="admin-alert admin-error">
            <ul style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="admin-heading">
        <div>
            <h2>Platform administrators</h2>
            <div class="admin-muted">
                Manage accounts with access to the central platform console.
            </div>
        </div>
        <span style="font-size:13px;color:#64748b;">
            {{ $admins->total() }} administrators
        </span>
    </div>

    <div class="admin-card">
        <h3 style="margin:0 0 8px;color:#142b40;font-size:17px;">
            Create administrator
        </h3>

        <p class="admin-muted" style="margin-bottom:22px;">
            Add another trusted administrator to the platform.
        </p>

        <form method="POST" action="{{ route('super.admins.store') }}">
            @csrf

            <div class="admin-grid">
                <div class="admin-field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                </div>

                <div class="admin-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255">
                </div>

                <div class="admin-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
                </div>

                <div class="admin-field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" autocomplete="new-password">
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="admin-btn admin-btn-primary">
                    Create administrator
                </button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <div class="admin-heading">
            <div>
                <h2>Administrator accounts</h2>
                <div class="admin-muted">View and manage platform administrator accounts.</div>
            </div>

            <form method="GET" action="{{ route('super.admins.index') }}" class="admin-search">
                <input type="search" name="search" value="{{ $search }}" placeholder="Search name or email...">
                <button class="admin-btn admin-btn-primary" type="submit">Search</button>
                @if($search)
                    <a href="{{ route('super.admins.index') }}" class="admin-btn" style="background:#f1f5f9;color:#334155;">Clear</a>
                @endif
            </form>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Email address</th>
                        <th>Date created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($admins as $admin)
                        <tr>
                            <td>
                                <span style="display:flex;align-items:center;min-width:180px;">
                                    <span class="admin-avatar">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </span>
                                    <strong>{{ $admin->name }}</strong>
                                </span>
                            </td>

                            <td>{{ $admin->email }}</td>

                            <td>{{ $admin->created_at?->format('d M Y') ?? '—' }}</td>

                            <td>
                                @if((int) auth('super')->id() === (int) $admin->id)
                                    <span style="font-size:12px;color:#64748b;">Current account</span>
                                @else
                                    <form method="POST"
                                        action="{{ route('super.admins.destroy', $admin) }}"
                                        onsubmit="return confirm('Are you sure you want to delete this administrator account?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="admin-btn admin-btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:35px;color:#718096;">
                                No administrator accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $admins->links() }}
        </div>
    </div>
</div>
@endsection
