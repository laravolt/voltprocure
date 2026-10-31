@props(['variant' => 'primary', 'href' => null])

@php
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-50 disabled:pointer-events-none '.match ($variant) {
        'secondary' => 'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800',
        'danger' => 'border border-red-300 bg-white text-red-700 hover:bg-red-50 dark:border-red-500/40 dark:bg-zinc-900 dark:text-red-300 dark:hover:bg-red-500/10',
        default => 'bg-accent text-white hover:bg-accent-strong',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
