{{-- Left-aligned filter row for the top of a flush card.

     Filters used to live in the card's actions slot, but that header is
     `justify-between`, so with no card title the controls were pushed to the far
     right and left a wide empty gap where the title would have been. A filter bar
     is its own row and starts at the left edge. --}}
<form method="GET" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 px-5 sm:px-6 py-3 border-b border-slate-100 bg-slate-50/60']) }}>
    {{ $slot }}
</form>
