@blaze
@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if ($title)
        <header class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-800">
            <div>
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
                @if ($description)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>
            {{ $action ?? '' }}
        </header>
    @endif
    {{ $slot }}
</section>
