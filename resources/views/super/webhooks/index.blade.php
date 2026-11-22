@extends('super.layout')
@section('content')
<h1>Webhooks</h1>

<form method="POST" action="{{ route('super.webhooks.store') }}" class="card">
    @csrf
    <label>Endpoint URL <input type="url" name="url" placeholder="https://example.com/hook" required></label>
    <fieldset>
        <legend>Events</legend>
        @foreach ($events as $event)
            <label><input type="checkbox" name="events[]" value="{{ $event }}"> {{ $event }}</label>
        @endforeach
    </fieldset>
    <button type="submit">Add webhook</button>
</form>

<h2>Endpoints</h2>
<table>
    <thead><tr><th>URL</th><th>Events</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($endpoints as $e)
        <tr>
            <td>{{ $e->url }}</td>
            <td>{{ implode(', ', $e->events) }}</td>
            <td>{{ $e->active ? 'Active' : 'Paused' }}</td>
            <td>
                <form method="POST" action="{{ route('super.webhooks.test', $e) }}" style="display:inline">@csrf<button>Test</button></form>
                <form method="POST" action="{{ route('super.webhooks.toggle', $e) }}" style="display:inline">@csrf<button>{{ $e->active ? 'Pause' : 'Resume' }}</button></form>
                <form method="POST" action="{{ route('super.webhooks.destroy', $e) }}" style="display:inline" onsubmit="return confirm('Delete this webhook?')">@csrf @method('DELETE')<button>Delete</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4">No webhooks yet.</td></tr>
    @endforelse
    </tbody>
</table>

<h2>Recent deliveries</h2>
<table>
    <thead><tr><th>When</th><th>Event</th><th>URL</th><th>Status</th><th>Attempts</th><th></th></tr></thead>
    <tbody>
    @forelse ($deliveries as $d)
        <tr>
            <td>{{ $d->created_at->format('d M H:i') }}</td>
            <td><code>{{ $d->event }}</code></td>
            <td>{{ $d->endpoint->url ?? '-' }}</td>
            <td>{{ $d->success ? 'OK ' . $d->status_code : 'Failed ' . ($d->status_code ?? '') }}</td>
            <td>{{ $d->attempts }}</td>
            <td>
                @unless ($d->success)
                    <form method="POST" action="{{ route('super.webhooks.resend', $d) }}">@csrf<button>Resend</button></form>
                @endunless
            </td>
        </tr>
    @empty
        <tr><td colspan="6">No deliveries yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $deliveries->links() }}
@endsection