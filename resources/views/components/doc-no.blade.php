@props(['number', 'href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'doc-no font-medium text-accent hover:text-accent-strong hover:underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent rounded-sm dark:text-indigo-300']) }}>{{ $number }}</a>
@else
    <span {{ $attributes->merge(['class' => 'doc-no']) }}>{{ $number }}</span>
@endif
