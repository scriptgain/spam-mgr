@props(['name'])
@php
    // Curated Heroicons (outline). Left-aligned by default per house style.
    // Paths live in App\Support\Icons so the favicon renderer reads the same set.
    $icons = \App\Support\Icons::PATHS;
@endphp
@php
    // merge() always prepends the default, so a caller passing w-7 h-7 rendered
    // "w-5 h-5 w-7 h-7" and the winner came down to stylesheet order, not markup
    // order. Only fall back to the default when no size was supplied.
    $hasSize = preg_match('/(?:^|\s)(?:w-|h-|size-)/', (string) $attributes->get('class', ''));
@endphp
<svg {{ $attributes->class(['w-5 h-5' => ! $hasSize]) }} fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
    {!! $icons[$name] ?? '' !!}
</svg>
