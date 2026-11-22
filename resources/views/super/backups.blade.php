@extends('super.layout')
@section('content')
<h1>Backups</h1>
<p>Daily at 02:00. Kept for {{ $retention }} days.</p>

<form method="POST" action="{{ route('super.backups.store') }}">
    @csrf
    <select name="tenant_id">
        <option value="">All companies</option>
        @foreach ($tenants as $t) <option value="{{ $t }}">{{ $t }}</option> @endforeach
    </select>
    <button type="submit">Backup now</button>
</form>

<form method="GET">
    <select name="tenant_id" onchange="this.form.submit()">
        <option value="">Filter: all</option>
        @foreach ($tenants as $t) <option value="{{ $t }}" @selected(request('tenant_id') === $t)>{{ $t }}</option> @endforeach
    </select>
</form>

<table>
    <thead><tr><th>When</th><th>Company</th><th>Size</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($backups as $b)
        <tr>
            <td>{{ $b->created_at->format('d M Y H:i') }}</td>
            <td>{{ $b->tenant_id }}</td>
            <td>{{ number_format($b->size / 1024, 1) }} KB</td>
            <td title="{{ $b->error }}">{{ ucfirst($b->status) }}</td>
            <td>
                @if ($b->status === 'completed')
                    <a href="{{ route('super.backups.download', $b) }}">Download</a>
                @endif
                <form method="POST" action="{{ route('super.backups.destroy', $b) }}" style="display:inline" onsubmit="return confirm('Delete this backup?')">@csrf @method('DELETE')<button>Delete</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">No backups yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $backups->links() }}
@endsection