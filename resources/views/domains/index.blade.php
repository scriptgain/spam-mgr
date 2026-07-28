<x-layouts.app title="Domains">
    <x-page-header title="Domains" icon="server"
        subtitle="Every domain you filter. Mail arrives at your MX nodes, gets scored, and is relayed on to the destination.">
        <x-slot:actions>
            @if (auth()->user()->isAdmin())
                <x-button href="{{ route('domains.create') }}" icon="plus" size="sm">Add Domain</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <x-filter-bar>
            <x-input name="q" value="{{ $search }}" placeholder="Search domains" class="w-56" />
            <x-button type="submit" variant="secondary" size="sm">Search</x-button>
        </x-filter-bar>

        @if ($domains->isEmpty())
            <x-empty-state icon="server" title="No Domains Yet"
                description="Add a domain, point its MX records at your nodes, then verify it." />
        @else
            <x-table flush>
                <thead>
                    <tr>
                        <th>Domain</th><th>Customer</th><th>Destination</th>
                        <th>Policy</th><th>Verified</th><th>MX</th>
                        <th class="text-right">Mailboxes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        <tr>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('domains.show', $domain) }}" class="hover:text-brand-700">{{ $domain->name }}</a>
                                @unless ($domain->active)<x-badge color="neutral" class="ml-1">Inactive</x-badge>@endunless
                            </td>
                            <td class="text-slate-500">{{ $domain->customer?->name }}</td>
                            <td class="text-slate-500">
                                {{ $domain->destination_host ?: 'Not set' }}@if ($domain->destination_host && $domain->destination_port != 25):{{ $domain->destination_port }}@endif
                            </td>
                            <td class="text-slate-500">{{ $domain->policy?->name ?? 'Default' }}</td>
                            <td>
                                <x-badge :color="$domain->isVerified() ? 'success' : 'warn'" dot>
                                    {{ $domain->isVerified() ? 'Verified' : 'Pending' }}
                                </x-badge>
                            </td>
                            <td>
                                @php $mxColor = ['verified' => 'success', 'failed' => 'danger'][$domain->mx_status] ?? 'neutral'; @endphp
                                <x-badge :color="$mxColor">{{ ucfirst($domain->mx_status) }}</x-badge>
                            </td>
                            <td class="text-right tabular">{{ number_format($domain->recipients_count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif

        @if ($domains->hasPages())
            <x-slot:footer>{{ $domains->links() }}</x-slot:footer>
        @endif
    </x-card>
</x-layouts.app>
