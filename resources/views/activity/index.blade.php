@extends('layouts.app')
@section('content')
<h2>Activity Logs</h2>

<form method="GET" style="margin-bottom:16px">
    <select name="user_id">
        <option value="">All users</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
    <select name="action">
        <option value="">All actions</option>
        @foreach($actions as $a)
            <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}">
    <input type="date" name="to" value="{{ request('to') }}">
    <button>Filter</button>
</form>

<table>
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr>
            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
            <td>{{ $log->user->name ?? 'System' }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ $log->description }}</td>
            <td>{{ $log->ip }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No activity found.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $logs->links() }}
@endsection