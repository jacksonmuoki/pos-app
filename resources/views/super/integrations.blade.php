
@extends('super.layout')

@section('content')

<style>
    .integrations-page {
        --int-primary: #128d51;
        --int-primary-dark: #0f7545;
        --int-text: #1e293b;
        --int-muted: #7b8a9c;
        --int-border: #e6eaef;
        --int-bg: #f8fafc;
        font-family: 'Manrope', sans-serif;
        color: var(--int-text);
    }

    .integrations-page * {
        box-sizing: border-box;
    }

    .integrations-page .integrations-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .integrations-page .integrations-title h1 {
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.7px;
        color: var(--int-text);
        margin: 0 0 6px;
    }

    .integrations-page .integrations-title p {
        font-size: 12px;
        color: var(--int-muted);
        margin: 0;
    }

    .integrations-page .integration-count {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        background: #effaf3;
        border: 1px solid #d7f0df;
        border-radius: 8px;
        color: var(--int-primary);
        font-size: 11px;
        font-weight: 800;
    }

    .integrations-page .integration-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .integrations-page .integration-card {
        background: #fff;
        border: 1px solid var(--int-border);
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
        min-width: 0;
        transition: border-color .2s, box-shadow .2s;
    }

    .integrations-page .integration-card:hover {
        border-color: #cbded2;
        box-shadow: 0 5px 15px rgba(15, 23, 42, .045);
    }

    .integrations-page .integration-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 17px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--int-border);
    }

    .integrations-page .integration-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .integrations-page .integration-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 10px;
        background: #effaf3;
        color: var(--int-primary);
        font-size: 17px;
        font-weight: 800;
    }

    .integrations-page .integration-heading h2 {
        font-size: 14px;
        font-weight: 800;
        margin: 0 0 4px;
        color: var(--int-text);
    }

    .integrations-page .integration-heading p {
        font-size: 11px;
        color: var(--int-muted);
        margin: 0;
    }

    .integrations-page .integration-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .integrations-page .integration-status.active {
        background: #ecfdf3;
        color: #128d51;
    }

    .integrations-page .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .integrations-page .integration-fields {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .integrations-page .integration-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .integrations-page .integration-field label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
    }

    .integrations-page .integration-field input {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid var(--int-border);
        border-radius: 8px;
        outline: none;
        background: #fff;
        color: var(--int-text);
        font-family: inherit;
        font-size: 12px;
        transition: border-color .2s, box-shadow .2s;
    }

    .integrations-page .integration-field input::placeholder {
        color: #a0acb9;
    }

    .integrations-page .integration-field input:focus {
        border-color: var(--int-primary);
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .09);
    }

    .integrations-page .integration-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 13px;
        padding-top: 18px;
        margin-top: 20px;
        border-top: 1px solid var(--int-border);
    }

    .integrations-page .integration-toggle {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
    }

    .integrations-page .integration-toggle input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: var(--int-primary);
        cursor: pointer;
    }

    .integrations-page .integration-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 37px;
        padding: 9px 15px;
        border: 0;
        border-radius: 8px;
        background: var(--int-primary);
        color: #fff;
        font-family: inherit;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: background .2s, transform .2s;
    }

    .integrations-page .integration-save:hover {
        background: var(--int-primary-dark);
        transform: translateY(-1px);
    }

    .integrations-page .integration-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 45px 20px;
        background: #fff;
        border: 1px dashed var(--int-border);
        border-radius: 12px;
    }

    .integrations-page .integration-empty h3 {
        font-size: 14px;
        margin: 0 0 6px;
    }

    .integrations-page .integration-empty p {
        font-size: 12px;
        color: var(--int-muted);
        margin: 0;
    }

    @media (max-width: 900px) {
        .integrations-page .integration-list {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 520px) {
        .integrations-page .integrations-title h1 {
            font-size: 20px;
        }

        .integrations-page .integration-card {
            padding: 17px;
        }

        .integrations-page .integration-footer {
            align-items: stretch;
        }

        .integrations-page .integration-save {
            width: 100%;
        }
    }
</style>

<div class="integrations-page">

    <div class="integrations-header">
        <div class="integrations-title">
            <h1>Integrations</h1>
            <p>Configure and manage external services connected to your platform.</p>
        </div>

        <div class="integration-count">
            {{ count($providers) }} Available Integrations
        </div>
    </div>

    <div class="integration-list">

        @forelse ($providers as $key => $provider)

            @php
                $config = $saved[$key]->config ?? [];
                $isEnabled = (bool) optional($saved[$key] ?? null)->enabled;
            @endphp

            <form method="POST"
                  action="{{ route('super.integrations.update', $key) }}"
                  class="integration-card">

                @csrf
                @method('PUT')

                <div class="integration-card-header">
                    <div class="integration-heading">
                        <div class="integration-icon">
                            {{ strtoupper(substr($provider['label'], 0, 1)) }}
                        </div>

                        <div>
                            <h2>{{ $provider['label'] }}</h2>
                            <p>Integration configuration</p>
                        </div>
                    </div>

                    <span class="integration-status {{ $isEnabled ? 'active' : '' }}">
                        <span class="status-dot"></span>
                        {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>

                <div class="integration-fields">

                    @foreach ($provider['fields'] as $name => $def)

                        <div class="integration-field">
                            <label for="integration_{{ $key }}_{{ $name }}">
                                {{ $def['label'] }}
                            </label>

                            @if ($def['secret'])
                                <input
                                    type="password"
                                    id="integration_{{ $key }}_{{ $name }}"
                                    name="config[{{ $name }}]"
                                    autocomplete="new-password"
                                    placeholder="{{ !empty($config[$name]) ? '•••••• saved (leave blank to keep)' : 'Enter ' . strtolower($def['label']) }}"
                                >
                            @else
                                <input
                                    type="text"
                                    id="integration_{{ $key }}_{{ $name }}"
                                    name="config[{{ $name }}]"
                                    value="{{ $config[$name] ?? '' }}"
                                    placeholder="Enter {{ strtolower($def['label']) }}"
                                >
                            @endif
                        </div>

                    @endforeach

                </div>

                <div class="integration-footer">

                    <label class="integration-toggle">
                        <input
                            type="checkbox"
                            name="enabled"
                            value="1"
                            @checked($isEnabled)
                        >
                        <span>Enable integration</span>
                    </label>

                    <button type="submit" class="integration-save">
                        Save {{ $provider['label'] }}
                    </button>

                </div>

            </form>

        @empty

            <div class="integration-empty">
                <h3>No integrations available</h3>
                <p>Integration providers will appear here when configured.</p>
            </div>

        @endforelse

    </div>

</div>

@endsection
