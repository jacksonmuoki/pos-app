@extends('super.layout')
@section('content')
<h1>Integrations</h1>
@foreach ($providers as $key => $provider)
    @php $config = $saved[$key]->config ?? []; @endphp
    <form method="POST" action="{{ route('super.integrations.update', $key) }}" class="card">
        @csrf @method('PUT')
        <h2>{{ $provider['label'] }}</h2>
        @foreach ($provider['fields'] as $name => $def)
            <label>{{ $def['label'] }}
                @if ($def['secret'])
                    <input type="password" name="config[{{ $name }}]" autocomplete="new-password"
                           placeholder="{{ !empty($config[$name]) ? '•••••• saved (leave blank to keep)' : '' }}">
                @else
                    <input name="config[{{ $name }}]" value="{{ $config[$name] ?? '' }}">
                @endif
            </label>
        @endforeach
        <label><input type="checkbox" name="enabled" value="1" @checked(optional($saved[$key] ?? null)->enabled)> Enabled</label>
        <button type="submit">Save {{ $provider['label'] }}</button>
    </form>
@endforeach
@endsection