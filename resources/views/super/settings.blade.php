@extends('super.layout')

@section('content')
<h1>Settings</h1>

<style>
    .tabs { display:flex; gap:4px; flex-wrap:wrap; border-bottom:2px solid #e4e7ec; margin-bottom:20px; }
    .tab { padding:10px 16px; cursor:pointer; border:0; background:none; color:#475467; font-weight:600; border-bottom:3px solid transparent; margin-bottom:-2px; }
    .tab.active { color:#1b7a3d; border-bottom-color:#1b7a3d; }
    .pane { display:none; } .pane.active { display:block; }
    .pane label { display:block; margin-bottom:14px; }
    .pane input[type=text], .pane input[type=email], .pane input[type=number], .pane input:not([type]), .pane textarea { display:block; width:100%; max-width:480px; padding:8px; }
    .pill { padding:2px 8px; border-radius:99px; font-size:12px; }
    .on { background:#dcfae6; color:#1b7a3d; } .off { background:#f2f4f7; color:#667085; } .warn { background:#fee4e2; color:#b42318; }
</style>

<div class="tabs" id="tabs">
    <button type="button" class="tab active" data-pane="general">General</button>
    <button type="button" class="tab" data-pane="currency">Currency &amp; locale</button>
    <button type="button" class="tab" data-pane="signup">Signup &amp; trial</button>
    <button type="button" class="tab" data-pane="maintenance">Maintenance</button>
    <button type="button" class="tab" data-pane="messaging">SMS &amp; payments</button>
    <button type="button" class="tab" data-pane="companies">Companies</button>
</div>

<form method="POST" action="{{ route('super.settings.update') }}" class="card">
    @csrf
    @method('PUT')

    <div class="pane active" id="pane-general">
        <label>Platform name <input name="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}" required></label>
        <label>Support email <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" required></label>
        <label>Support phone <input name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}"></label>
    </div>

    <div class="pane" id="pane-currency">
        <p>These are defaults. Each company can set its own currency and timezone.</p>
        <label>Default currency (3 letters) <input name="default_currency" maxlength="3" value="{{ old('default_currency', $settings['default_currency']) }}" required></label>
        <label>Default timezone <input name="default_timezone" value="{{ old('default_timezone', $settings['default_timezone']) }}" required></label>
        <label>Date format (PHP) <input name="date_format" value="{{ old('date_format', $settings['date_format']) }}" required></label>
    </div>

    <div class="pane" id="pane-signup">
        <label>Trial length (days) <input type="number" name="trial_days" min="0" max="365" value="{{ old('trial_days', $settings['trial_days']) }}" required></label>
        <label><input type="checkbox" name="signup_enabled" value="1" @checked(old('signup_enabled', $settings['signup_enabled']) == '1')> Allow company signup</label>
    </div>

    <div class="pane" id="pane-maintenance">
        <label><input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode']) == '1')> <strong>Platform-wide maintenance</strong> (blocks every company; the super admin area stays available)</label>
        <label>Message shown to users <textarea name="maintenance_message" rows="3" maxlength="500" required>{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea></label>
        <label>IP addresses allowed through (comma separated)
            <input name="maintenance_ips" value="{{ old('maintenance_ips', $settings['maintenance_ips']) }}" placeholder="41.90.1.10, 105.160.2.5">
        </label>
        <small>Payment callbacks are never blocked. To pause a single company, use its page in the Companies tab.</small>
    </div>

    <div class="pane" id="pane-messaging">
        <p>Platform SMS and payment defaults are managed in <a href="{{ route('super.integrations') }}">Integrations</a>.</p>
        <p>Each company keeps its <strong>own PayHero credentials</strong> and may use its own SMS key; open a company in the Companies tab to edit them.</p>
    </div>

    <button type="submit" id="save-btn">Save settings</button>
</form>

<div class="pane" id="pane-companies">
    <h2>Company settings</h2>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Company</th><th>Business name</th><th>Currency</th><th>M-Pesa</th><th>SMS</th><th>Maintenance</th><th></th></tr></thead>
        <tbody>
        @forelse ($companies as $c)
            <tr>
                <td>{{ $c['id'] }}</td>
                <td>{{ $c['name'] }}</td>
                <td>{{ $c['currency'] }}</td>
                <td><span class="pill {{ $c['payhero'] ? 'on' : 'off' }}">{{ $c['payhero'] ? 'Configured' : 'Not set' }}</span></td>
                <td><span class="pill {{ $c['sms'] ? 'on' : 'off' }}">{{ $c['sms'] ? 'On' : 'Off' }}</span></td>
                <td><span class="pill {{ $c['maintenance'] ? 'warn' : 'off' }}">{{ $c['maintenance'] ? 'In maintenance' : 'Live' }}</span></td>
                <td>
                    @if ($c['error']) <span class="pill warn">DB unreachable</span>
                    @else <a href="{{ route('super.company-settings.edit', $c['id']) }}">Edit</a> @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No companies yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<script>
    const tabs = document.querySelectorAll('.tab');
    const saveBtn = document.getElementById('save-btn');
    function show(name) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.pane === name));
        document.querySelectorAll('.pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + name));
        saveBtn.style.display = name === 'companies' ? 'none' : '';
        history.replaceState(null, '', '#' + name);
    }
    tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.pane)));
    show((location.hash || '#general').slice(1));
</script>
@endsection