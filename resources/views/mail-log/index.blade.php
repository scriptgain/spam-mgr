<x-layouts.app title="Mail Log">
    <x-page-header title="Mail Log" icon="book"
        subtitle="Every message your nodes handled, clean or not. Pruned automatically on the retention schedule." />

    <x-card flush>
        <x-filter-bar>
            <x-select name="domain" class="w-44" onchange="this.form.submit()">
                <option value="">All Domains</option>
                @foreach ($domains as $domain)
                    <option value="{{ $domain->id }}" @selected($filters['domainId'] == $domain->id)>{{ $domain->name }}</option>
                @endforeach
            </x-select>
            <x-select name="verdict" class="w-36" onchange="this.form.submit()">
                <option value="">All Verdicts</option>
                @foreach (['clean' => 'Clean', 'tagged' => 'Tagged', 'quarantined' => 'Quarantined', 'rejected' => 'Rejected'] as $v => $label)
                    <option value="{{ $v }}" @selected($filters['verdict'] === $v)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select name="delivery" class="w-36" onchange="this.form.submit()">
                <option value="">All Delivery</option>
                @foreach (['delivered' => 'Delivered', 'pending' => 'Pending', 'deferred' => 'Deferred', 'failed' => 'Failed'] as $d => $label)
                    <option value="{{ $d }}" @selected($filters['delivery'] === $d)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input name="q" value="{{ $filters['search'] }}" placeholder="Sender, recipient, subject" class="w-56" />
            <x-button type="submit" variant="secondary" size="sm">Search</x-button>
        </x-filter-bar>

        @if ($entries->isEmpty())
            <x-empty-state icon="book" title="No Mail Logged"
                description="Once your nodes start handling mail it appears here." />
        @else
            <form method="POST" action="{{ route('mail-log.bulk') }}" x-data="{ selected: [] }">
                @csrf
                <div class="flex items-center justify-between gap-4 px-5 sm:px-6 py-3 border-b border-slate-100 bg-slate-50/60">
                    <p class="text-sm text-slate-500" x-text="selected.length ? selected.length + ' selected' : 'Select entries to act on them'"></p>
                    <div class="flex items-center gap-2" x-show="selected.length" x-cloak>
                        <x-button type="submit" name="action" value="retry" size="sm" icon="refresh">Retry Deferred</x-button>
                        <x-button type="submit" name="action" value="delete" size="sm" variant="danger" icon="trash">Delete</x-button>
                    </div>
                </div>

                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>When</th><th>From</th><th>To</th><th>Subject</th>
                            <th>Verdict</th><th class="text-right">Score</th><th>Delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            @php
                                $verdictColor = ['clean' => 'success', 'tagged' => 'warn', 'quarantined' => 'danger', 'rejected' => 'danger'][$entry->verdict] ?? 'neutral';
                                $deliveryColor = ['delivered' => 'success', 'deferred' => 'warn', 'failed' => 'danger'][$entry->delivery_status] ?? 'neutral';
                            @endphp
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $entry->id }}" x-model="selected" class="rounded border-slate-300"></td>
                                <td class="text-slate-500">{{ $entry->logged_at?->diffForHumans() }}</td>
                                <td class="text-slate-500">{{ $entry->sender ?: 'Unknown' }}</td>
                                <td class="text-slate-500">{{ $entry->recipient }}</td>
                                <td class="font-medium text-slate-900">{{ \Illuminate\Support\Str::limit($entry->subject ?: '(no subject)', 50) }}</td>
                                <td><x-badge :color="$verdictColor">{{ ucfirst($entry->verdict) }}</x-badge></td>
                                <td class="text-right tabular">{{ $entry->score !== null ? number_format($entry->score, 1) : '' }}</td>
                                <td><x-badge :color="$deliveryColor">{{ ucfirst($entry->delivery_status) }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </form>
        @endif

        @if ($entries->hasPages())
            <x-slot:footer>{{ $entries->links() }}</x-slot:footer>
        @endif
    </x-card>
</x-layouts.app>
