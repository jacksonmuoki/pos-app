
@extends('super.layout')

@section('content')

<style>
    .webhooks-page {
        --wh-primary: #128d51;
        --wh-primary-dark: #0f7545;
        --wh-text: #1e293b;
        --wh-muted: #7b8a9c;
        --wh-border: #e6eaef;
        --wh-bg: #f8fafc;
        font-family: 'Manrope', sans-serif;
        color: var(--wh-text);
    }

    .webhooks-page * {
        box-sizing: border-box;
    }

    .webhooks-page .wh-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 24px;
    }

    .webhooks-page .wh-header h1 {
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.7px;
        margin: 0 0 6px;
        color: var(--wh-text);
    }

    .webhooks-page .wh-header p {
        color: var(--wh-muted);
        font-size: 12px;
        margin: 0;
    }

    .webhooks-page .wh-section {
        background: #fff;
        border: 1px solid var(--wh-border);
        border-radius: 12px;
        padding: 23px;
        margin-bottom: 22px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    .webhooks-page .wh-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--wh-border);
    }

    .webhooks-page .wh-section-header h2 {
        font-size: 14px;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .webhooks-page .wh-section-header h2::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 5px;
        background: var(--wh-primary);
    }

    .webhooks-page .wh-section-header span {
        color: var(--wh-muted);
        font-size: 11px;
    }

    .webhooks-page .wh-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 18px;
    }

    .webhooks-page .wh-field label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
    }

    .webhooks-page .wh-field input {
        width: 100%;
        min-height: 43px;
        padding: 10px 13px;
        border: 1px solid var(--wh-border);
        border-radius: 8px;
        background: #fff;
        color: var(--wh-text);
        outline: none;
        font-family: inherit;
        font-size: 12px;
        transition: border-color .2s, box-shadow .2s;
    }

    .webhooks-page .wh-field input:focus {
        border-color: var(--wh-primary);
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .09);
    }

    .webhooks-page .wh-field input::placeholder {
        color: #a0acb9;
    }

    .webhooks-page .wh-events {
        border: 1px solid var(--wh-border);
        border-radius: 9px;
        padding: 16px;
        margin: 0 0 19px;
        min-width: 0;
    }

    .webhooks-page .wh-events legend {
        padding: 0 7px;
        font-size: 11px;
        font-weight: 800;
        color: #475569;
    }

    .webhooks-page .wh-event-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .webhooks-page .wh-event-option {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 10px 12px;
        border: 1px solid var(--wh-border);
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        overflow-wrap: anywhere;
        transition: background .2s, border-color .2s;
    }

    .webhooks-page .wh-event-option:hover {
        background: #f7fcf8;
        border-color: #b9dfc8;
    }

    .webhooks-page .wh-event-option input {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
        margin: 0;
        accent-color: var(--wh-primary);
    }

    .webhooks-page .wh-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        min-height: 34px;
        padding: 8px 12px;
        border: 1px solid transparent;
        border-radius: 7px;
        font-family: inherit;
        font-size: 10px;
        font-weight: 800;
        cursor: pointer;
        transition: background .2s, border-color .2s;
        white-space: nowrap;
    }

    .webhooks-page .wh-btn-primary {
        background: var(--wh-primary);
        color: #fff;
    }

    .webhooks-page .wh-btn-primary:hover {
        background: var(--wh-primary-dark);
    }

    .webhooks-page .wh-btn-light {
        background: #f1f5f9;
        color: #475569;
        border-color: var(--wh-border);
    }

    .webhooks-page .wh-btn-light:hover {
        background: #e8eef4;
    }

    .webhooks-page .wh-btn-danger {
        background: #fff1f2;
        color: #dc2626;
        border-color: #ffe0e3;
    }

    .webhooks-page .wh-btn-danger:hover {
        background: #ffe4e6;
    }

    .webhooks-page .wh-btn-success {
        background: #ecfdf3;
        color: var(--wh-primary);
        border-color: #d1f2dd;
    }

    .webhooks-page .wh-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .webhooks-page table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
        text-align: left;
    }

    .webhooks-page table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .45px;
        padding: 13px 14px;
        border-bottom: 1px solid var(--wh-border);
        white-space: nowrap;
    }

    .webhooks-page table td {
        padding: 14px;
        border-bottom: 1px solid #edf0f3;
        color: #475569;
        font-size: 11px;
        vertical-align: middle;
    }

    .webhooks-page table tbody tr:last-child td {
        border-bottom: 0;
    }

    .webhooks-page table tbody tr:hover {
        background: #fafcfb;
    }

    .webhooks-page .wh-url {
        color: var(--wh-text);
        font-weight: 700;
        max-width: 260px;
        overflow-wrap: anywhere;
    }

    .webhooks-page .wh-events-text {
        max-width: 230px;
        overflow-wrap: anywhere;
        line-height: 1.7;
    }

    .webhooks-page .wh-code {
        display: inline-block;
        padding: 4px 7px;
        border-radius: 5px;
        background: #f1f5f9;
        color: #475569;
        font-size: 10px;
        overflow-wrap: anywhere;
    }

    .webhooks-page .wh-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .webhooks-page .wh-status.active {
        background: #ecfdf3;
        color: #128d51;
    }

    .webhooks-page .wh-status.paused {
        background: #f1f5f9;
        color: #64748b;
    }

    .webhooks-page .wh-status.failed {
        background: #fff1f2;
        color: #dc2626;
    }

    .webhooks-page .wh-status.success {
        background: #ecfdf3;
        color: #128d51;
    }

    .webhooks-page .wh-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    .webhooks-page .wh-actions form {
        display: inline-flex;
        margin: 0;
    }

    .webhooks-page .wh-empty {
        text-align: center;
        padding: 30px 15px !important;
        color: var(--wh-muted) !important;
        font-size: 12px !important;
    }

    .webhooks-page .wh-pagination {
        padding-top: 18px;
    }

    .webhooks-page .wh-pagination nav {
        display: flex;
        justify-content: flex-end;
    }

    .webhooks-page .wh-pagination svg {
        width: 16px;
        height: 16px;
    }

    .webhooks-page .wh-pagination a,
    .webhooks-page .wh-pagination span {
        font-size: 11px;
    }

    @media (max-width: 900px) {
        .webhooks-page .wh-event-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .webhooks-page .wh-header h1 {
            font-size: 20px;
        }

        .webhooks-page .wh-section {
            padding: 16px;
        }

        .webhooks-page .wh-event-grid {
            grid-template-columns: 1fr;
        }

        .webhooks-page .wh-section-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="webhooks-page">

    {{-- Page heading --}}
    <div class="wh-header">
        <div>
            <h1>Webhooks</h1>
            <p>Manage event notifications and monitor outgoing webhook deliveries.</p>
        </div>
    </div>

    {{-- Create webhook --}}
    <section class="wh-section">

        <div class="wh-section-header">
            <h2>Add New Webhook</h2>
            <span>Connect an external application</span>
        </div>

        <form method="POST" action="{{ route('super.webhooks.store') }}">
            @csrf

            <div class="wh-field">
                <label for="webhook_url">Endpoint URL</label>
                <input
                    type="url"
                    id="webhook_url"
                    name="url"
                    placeholder="https://example.com/hook"
                    required
                >
            </div>

            <fieldset class="wh-events">
                <legend>Select Events</legend>

                <div class="wh-event-grid">
                    @foreach ($events as $event)
                        <label class="wh-event-option">
                            <input
                                type="checkbox"
                                name="events[]"
                                value="{{ $event }}"
                            >
                            <span>{{ $event }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <button type="submit" class="wh-btn wh-btn-primary">
                + Add Webhook
            </button>
        </form>

    </section>

    {{-- Registered endpoints --}}
    <section class="wh-section">

        <div class="wh-section-header">
            <h2>Registered Endpoints</h2>
            <span>{{ $endpoints->count() }} endpoints</span>
        </div>

        <div class="wh-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Endpoint URL</th>
                        <th>Subscribed Events</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($endpoints as $e)
                        <tr>
                            <td>
                                <div class="wh-url">{{ $e->url }}</div>
                            </td>

                            <td>
                                <div class="wh-events-text">
                                    {{ implode(', ', $e->events) }}
                                </div>
                            </td>

                            <td>
                                @if ($e->active)
                                    <span class="wh-status active">● Active</span>
                                @else
                                    <span class="wh-status paused">● Paused</span>
                                @endif
                            </td>

                            <td>
                                <div class="wh-actions">

                                    <form method="POST"
                                          action="{{ route('super.webhooks.test', $e) }}">
                                        @csrf
                                        <button type="submit" class="wh-btn wh-btn-light">
                                            Test
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('super.webhooks.toggle', $e) }}">
                                        @csrf
                                        <button type="submit" class="wh-btn wh-btn-success">
                                            {{ $e->active ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('super.webhooks.destroy', $e) }}"
                                          onsubmit="return confirm('Delete this webhook?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="wh-btn wh-btn-danger">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="wh-empty">
                                No webhook endpoints registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    {{-- Recent deliveries --}}
    <section class="wh-section">

        <div class="wh-section-header">
            <h2>Recent Deliveries</h2>
            <span>Webhook delivery activity</span>
        </div>

        <div class="wh-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Event</th>
                        <th>Endpoint</th>
                        <th>Delivery Status</th>
                        <th>Attempts</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($deliveries as $d)
                        <tr>
                            <td>
                                {{ $d->created_at->format('d M Y, H:i') }}
                            </td>

                            <td>
                                <code class="wh-code">{{ $d->event }}</code>
                            </td>

                            <td>
                                <div class="wh-url">
                                    {{ $d->endpoint->url ?? '-' }}
                                </div>
                            </td>

                            <td>
                                @if ($d->success)
                                    <span class="wh-status success">
                                        ● OK {{ $d->status_code }}
                                    </span>
                                @else
                                    <span class="wh-status failed">
                                        ● Failed {{ $d->status_code ?? '' }}
                                    </span>
                                @endif
                            </td>

                            <td>{{ $d->attempts }}</td>

                            <td>
                                @unless ($d->success)
                                    <form method="POST"
                                          action="{{ route('super.webhooks.resend', $d) }}">
                                        @csrf
                                        <button type="submit" class="wh-btn wh-btn-primary">
                                            Resend
                                        </button>
                                    </form>
                                @else
                                    <span style="color:#94a3b8;font-size:11px;">—</span>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="wh-empty">
                                No webhook deliveries recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="wh-pagination">
            {{ $deliveries->links() }}
        </div>

    </section>

</div>

@endsection
