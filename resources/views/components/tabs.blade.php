@props(['options', 'current', 'param' => 'status'])

{{-- Status filter as links: shareable URLs, works without JavaScript. --}}
<nav class="-mb-px flex gap-x-1 overflow-x-auto" aria-label="Saring status">
    @foreach ($options as $value => $label)
        @php($active = (string) $current === (string) $value)
        <a href="{{ request()->fullUrlWithQuery([$param => $value === '' ? null : $value, 'page' => null]) }}"
           @if ($active) aria-current="page" @endif
           class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-accent {{ $active ? 'border-accent text-zinc-900 dark:text-zinc-50' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
