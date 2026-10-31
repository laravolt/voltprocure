@props(['label', 'href' => null])

@php($tag = $href ? 'a' : 'div')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group block rounded-xl border border-zinc-200 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900'.($href ? ' transition-colors duration-150 hover:border-zinc-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:hover:border-zinc-700' : '')]) }}>
    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-zinc-50">{{ $slot }}</p>
    @isset($note)
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $note }}</p>
    @endisset
</{{ $tag }}>
