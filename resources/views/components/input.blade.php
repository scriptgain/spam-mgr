@props(['type' => 'text'])
@php
    // Same merge() trap as the select: a caller passing w-52 got "w-full ... w-52" and
    // stylesheet order decided, not markup order. Only apply w-full when the caller
    // did not size it.
    $hasWidth = (bool) preg_match('/(?:^|\s)(?:w-|min-w-|max-w-|basis-|size-)/', (string) $attributes->get('class', ''));
@endphp
<input type="{{ $type }}" {{ $attributes->class([
    'block rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 '
    . 'ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 '
    . 'focus:ring-2 focus:ring-inset focus:ring-brand-500 disabled:opacity-60 disabled:bg-slate-50',
    'w-full' => ! $hasWidth,
]) }}>
