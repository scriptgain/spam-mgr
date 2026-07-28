<x-layouts.app title="Quarantine">
    <x-page-header title="Quarantine" icon="inbox"
        subtitle="Mail held before it reached the recipient. Releasing queues it for the node that caught it." />

    <x-card flush>
        <x-filter-bar>
            <x-select name="domain" class="w-48" onchange="this.form.submit()">
                <option value="">All Domains</option>
                @foreach ($domains as $domain)
                    <option value="{{ $domain->id }}" @selected($domainId == $domain->id)>{{ $domain->name }}</option>
                @endforeach
            </x-select>
            <x-input name="q" value="{{ $search }}" placeholder="Sender, recipient or subject" class="w-64" />
            <x-button type="submit" variant="secondary" size="sm">Search</x-button>
        </x-filter-bar>

        @if ($messages->isEmpty())
            <x-empty-state icon="inbox" title="Nothing Held"
                description="No mail is currently in quarantine." />
        @else
            <form method="POST" action="{{ route('quarantine.bulk') }}" x-data="{ selected: [] }">
                @csrf
                <div class="flex items-center justify-between gap-4 px-5 sm:px-6 py-3 border-b border-slate-100 bg-slate-50/60">
                    <p class="text-sm text-slate-500" x-text="selected.length ? selected.length + ' selected' : 'Select messages to act on them'"></p>
                    <div class="flex items-center gap-2" x-show="selected.length" x-cloak>
                        <x-button type="submit" name="action" value="release" size="sm" icon="check">Release</x-button>
                        <x-button type="submit" name="action" value="delete" size="sm" variant="danger" icon="trash">Delete</x-button>
                    </div>
                </div>

                <x-table flush>
                    <thead>
                        <tr>
                            <th class="w-10"><input type="checkbox" class="rounded border-slate-300"
                                @change="selected = $event.target.checked ? Array.from(document.querySelectorAll('[name=\'ids[]\']')).map(i => i.value) : []"></th>
                            <th>From</th><th>To</th><th>Subject</th><th>Domain</th>
                            <th class="text-right">Score</th><th>Held</th><th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $message->id }}" x-model="selected" class="rounded border-slate-300"></td>
                                <td class="text-slate-500">{{ $message->sender ?: 'Unknown' }}</td>
                                <td class="text-slate-500">{{ $message->recipient }}</td>
                                <td class="font-medium text-slate-900">
                                    <a href="{{ route('quarantine.show', $message) }}" class="hover:text-brand-700">{{ $message->shortSubject() }}</a>
                                </td>
                                <td class="text-slate-500">{{ $message->domain?->name }}</td>
                                <td class="text-right tabular">{{ $message->spam_score !== null ? number_format($message->spam_score, 1) : '' }}</td>
                                <td class="text-slate-500">{{ $message->quarantined_at?->diffForHumans() }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <x-icon-button :href="route('quarantine.show', $message)" icon="eye" title="View" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </form>
        @endif

        @if ($messages->hasPages())
            <x-slot:footer>{{ $messages->links() }}</x-slot:footer>
        @endif
    </x-card>
</x-layouts.app>
