@extends('super.layout')

@section('content')
<h1>System health</h1>

@php $problems = collect($checks)->where('ok', false)->count() + collect($tenantRows)->where('ok', false)->count(); @endphp
<p style="color: {{ $problems ? '#b42318' : '#1b7a3d' }}; font-weight:600">
    {{ $problems ? "{$problems} item(s) need attention" : 'All systems healthy' }}
</p>

<h2>Platform</h2>
<div style="overflow-x:auto">
<table>
    <thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
    <tbody>
    @foreach ($checks as $c)
        <tr>
            <td>{{ $c['name'] }}</td>
            <td style="color: {{ $c['ok'] ? '#1b7a3d' : '#b42318' }}">{{ $c['ok'] ? '● Healthy' : '● Problem' }}</td>
            <td>{{ $c['detail'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

<h2>Companies</h2>
<div style="overflow-x:auto">
<table>
    <thead><tr><th>Company</th><th>Database</th><th>Migrations</th><th>Last backup</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($tenantRows as $t)
        <tr>
            <td>{{ $t['id'] }}</td>
            <td>{{ $t['db'] }}</td>
            <td>{{ $t['migrations'] }}</td>
            <td>{{ $t['backup'] }}</td>
            <td style="color: {{ $t['ok'] ? '#1b7a3d' : '#b42318' }}">{{ $t['ok'] ? '● OK' : '● Attention' }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No companies yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection