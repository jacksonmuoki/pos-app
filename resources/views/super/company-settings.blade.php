@extends('super.layout')

@section('content')
<h1>Settings for {{ $company->id }}</h1>
<p><a href="{{ route('super.settings') }}#companies">← Back to settings</a></p>

<form method="POST" action="{{ route('super.company-settings.update', $company) }}" class="card">
    @csrf
    @method('PUT')

    @include('partials.company-settings-fields')

    <h2>Maintenance (this company only)</h2>
    <label>
        <input type="checkbox" name="maintenance_mode" value="1"
               @checked(old('maintenance_mode', $values['maintenance_mode'] ?? '0') == '1')>
        Put this company in maintenance mode
    </label>
    <label>Message shown to staff
        <textarea name="maintenance_message" rows="3" maxlength="500">{{ old('maintenance_message', $values['maintenance_message'] ?? '') }}</textarea>
    </label>

    <button type="submit">Save company settings</button>
</form>
@endsection