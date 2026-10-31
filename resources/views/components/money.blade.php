@props(['amount', 'compact' => false])

@php
    $text = $compact && abs($amount) >= 1_000_000
        ? number_format($amount / 1_000_000, 1, ',', '.').' jt'
        : number_format($amount, 0, ',', '.');
@endphp

<span {{ $attributes->merge(['class' => 'tabular-nums whitespace-nowrap']) }}>Rp&nbsp;{{ $text }}</span>
