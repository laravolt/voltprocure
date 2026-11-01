@blaze
@props(['title', 'eyebrow' => null])

<header class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-xl font-semibold tracking-tight text-zinc-900 text-balance dark:text-zinc-50">{{ $title }}</h1>
        @isset($meta)
            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $meta }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
