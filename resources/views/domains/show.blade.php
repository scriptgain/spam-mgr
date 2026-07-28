<x-layouts.app :title="$domain->name">
    <x-page-header :title="$domain->name" icon="server"
        :subtitle="'Filtered for ' . ($domain->customer?->name ?? 'no customer')" :back="route('domains.index')">
        <x-slot:actions>
            @if (auth()->user()->isAdmin())
                <x-button href="{{ route('domains.edit', $domain) }}" icon="edit" variant="secondary" size="sm">Edit</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @unless ($domain->isVerified())
        <x-alert type="warn" class="mb-6" title="Not Verified Yet">
            <p>Your nodes will not accept mail for this domain until you prove you control it. Add this TXT record at the domain's apex, then verify:</p>
            <code class="mt-2 block rounded bg-white/60 px-3 py-2 text-xs font-mono ring-1 ring-inset ring-amber-200 break-all">{{ $domain->verificationRecord() }}</code>
            <form method="POST" action="{{ route('domains.verify', $domain) }}" class="mt-3">
                @csrf
                <x-button type="submit" size="sm">Verify Now</x-button>
            </form>
        </x-alert>
    @endunless

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat label="Mailboxes" :value="number_format($recipientCount)" icon="users" />
        <x-stat label="In Quarantine" :value="number_format($quarantineCount)" icon="archive" />
        <x-stat label="Policy" :value="$domain->policy?->name ?? 'Default'" icon="filter" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Delivery" subtitle="Where clean mail goes after filtering.">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Destination</dt>
                    <dd class="font-medium text-slate-900">{{ $domain->destination_host ?: 'Not set' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Port</dt>
                    <dd class="font-medium text-slate-900 tabular">{{ $domain->destination_port }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">TLS Policy</dt>
                    <dd class="font-medium text-slate-900">{{ ucfirst($domain->tls_policy) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Recipient Mode</dt>
                    <dd class="font-medium text-slate-900">
                        {{ $domain->recipient_mode === 'list' ? 'Known Mailboxes Only' : 'Accept All' }}
                    </dd>
                </div>
            </dl>

            <x-slot:footer>
                <form method="POST" action="{{ route('domains.check-mx', $domain) }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" icon="refresh">Check MX Records</x-button>
                </form>
            </x-slot:footer>
        </x-card>

        <x-card title="Status">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">Ownership</dt>
                    <dd>
                        <x-badge :color="$domain->isVerified() ? 'success' : 'warn'" dot>
                            {{ $domain->isVerified() ? 'Verified' : 'Pending' }}
                        </x-badge>
                    </dd>
                </div>
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">MX Records</dt>
                    <dd>
                        @php $mxColor = ['verified' => 'success', 'failed' => 'danger'][$domain->mx_status] ?? 'neutral'; @endphp
                        <x-badge :color="$mxColor">{{ ucfirst($domain->mx_status) }}</x-badge>
                    </dd>
                </div>
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">Filtering</dt>
                    <dd><x-badge :color="$domain->active ? 'success' : 'neutral'">{{ $domain->active ? 'Active' : 'Paused' }}</x-badge></dd>
                </div>
            </dl>

            <x-slot:footer>
                <div class="flex flex-wrap gap-2">
                    <x-button href="{{ route('mailboxes.index', ['domain' => $domain->id]) }}" variant="secondary" size="sm">Mailboxes</x-button>
                    <x-button href="{{ route('quarantine.index', ['domain' => $domain->id]) }}" variant="secondary" size="sm">Quarantine</x-button>
                    <x-button href="{{ route('mail-log.index', ['domain' => $domain->id]) }}" variant="secondary" size="sm">Mail Log</x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </div>
</x-layouts.app>
