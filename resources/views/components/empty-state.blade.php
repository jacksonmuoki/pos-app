@props(['title', 'text' => null])

<div {{ $attributes->class(['empty-state']) }}>
    <strong>{{ $title }}</strong>
    @if ($text)
        <p>{{ $text }}</p>
    @endif
    @if (trim($slot))
        <div class="empty-state-action">{{ $slot }}</div>
    @endif
</div>