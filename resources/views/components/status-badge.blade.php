@props(['active' => true, 'activeText' => 'Active', 'inactiveText' => 'Suspended'])

<span {{ $attributes->class(['badge', $active ? 'active' : 'suspended']) }}>
    {{ $active ? $activeText : $inactiveText }}
</span>