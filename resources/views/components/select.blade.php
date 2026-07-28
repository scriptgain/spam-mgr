@php
    $classes = (string) $attributes->get('class', '');

    // A caller-supplied width has to win. $attributes->merge() PREPENDS the component
    // default, so class="w-44" rendered as "block w-full ... w-44" and the winner came
    // down to stylesheet order rather than markup order. w-full won, so every filter
    // select went full width and the filter rows stacked on top of each other.
    //
    // The width also belongs on the relative wrapper, because that div is the flex item
    // the parent row lays out. Sizing only the <select> inside a full-width wrapper
    // changes nothing.
    $hasWidth = (bool) preg_match('/(?:^|\s)(?:w-|min-w-|max-w-|basis-|size-)/', $classes);

    $base = 'block w-full appearance-none rounded-lg border-0 bg-white pl-3 pr-11 py-2 text-sm text-slate-900 '
        . 'ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 disabled:opacity-60';
@endphp
<div class="relative {{ $hasWidth ? $classes : 'w-full' }}">
    <select {{ $attributes->except('class') }} class="{{ $base }}">
        {{ $slot }}
    </select>
    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
</div>
