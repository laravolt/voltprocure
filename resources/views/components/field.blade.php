@props(['label', 'name', 'hint' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $label }}</label>
    <div class="mt-1.5">{{ $slot }}</div>
    @error($name)
        <p class="mt-1.5 text-sm text-red-700 dark:text-red-300" id="{{ $name }}-error">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @enderror
</div>
