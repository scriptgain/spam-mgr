<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" icon="dashboard"
        subtitle="Mail handled in the last 24 hours, what was held, and how your gateway is doing." />

    {{-- The blocked share leads: it is the number that justifies the gateway. --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat label="Blocked (24h)" :value="number_format($blocked24h)" icon="filter"
            :trend="$handled24h > 0 ? $blockedPercent . '% Of All Mail' : null" trend-color="success" />
        <x-stat label="Handled (24h)" :value="number_format($handled24h)" icon="inbox" />
        <x-stat label="In Quarantine" :value="number_format($heldTotal)" icon="archive" />
        <x-stat label="Deferred" :value="number_format($deferred)" icon="clock"
            :trend="$deferred > 0 ? 'Needs Attention' : null" trend-color="danger" />
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 @if($isOperator) lg:grid-cols-3 @endif gap-4">
        <x-stat label="Active Domains" :value="number_format($domains)" icon="server" />
        <x-stat label="Mailboxes" :value="number_format($mailboxes)" icon="users" />
        @if ($isOperator)
            <x-stat label="Customers" :value="number_format($customers)" icon="users" />
        @endif
    </div>

    @if ($isOperator && $unverified > 0)
        <x-alert type="warn" class="mt-6">
            {{ $unverified }} domain(s) are not verified yet, so the nodes will not accept mail for them.
            <a href="{{ route('domains.index') }}" class="font-medium underline">Review domains</a>
        </x-alert>
    @endif

    <div class="mt-6 grid grid-cols-1 @if($isOperator) lg:grid-cols-2 @endif gap-6">
        <x-card title="Latest Quarantine" subtitle="The ten most recent messages held." flush>
            <x-slot:actions>
                <x-button href="{{ route('quarantine.index') }}" variant="secondary" size="sm">View All</x-button>
            </x-slot:actions>

            @if ($heldRecent->isEmpty())
                <x-empty-state icon="inbox" title="Nothing Held"
                    description="No mail has been quarantined yet." />
            @else
                <x-table flush>
                    <thead>
                        <tr><th>From</th><th>Subject</th><th>Domain</th><th class="text-right">Score</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($heldRecent as $message)
                            <tr>
                                <td class="text-slate-500">{{ $message->sender ?: 'Unknown' }}</td>
                                <td class="font-medium text-slate-900">
                                    <a href="{{ route('quarantine.show', $message) }}" class="hover:text-brand-700">{{ $message->shortSubject(50) }}</a>
                                </td>
                                <td class="text-slate-500">{{ $message->domain?->name }}</td>
                                <td class="text-right tabular">{{ $message->spam_score !== null ? number_format($message->spam_score, 1) : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        @if ($isOperator)
            <x-card title="MX Nodes" subtitle="Your filtering gateways. Run as many as you like." flush>
                <x-slot:actions>
                    <x-button href="{{ route('nodes.index') }}" variant="secondary" size="sm">Manage</x-button>
                </x-slot:actions>

                @if ($nodes->isEmpty())
                    <x-empty-state icon="cloud" title="No Nodes Yet"
                        description="Add an MX node and enrol it to start filtering mail." />
                @else
                    <x-table flush>
                        <thead>
                            <tr><th>Hostname</th><th>Status</th><th>Services</th><th class="text-right">Queue</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($nodes as $node)
                                @php
                                    $status = $node->displayStatus();
                                    $statusColor = ['online' => 'success', 'stale' => 'warn'][$status] ?? 'neutral';
                                @endphp
                                <tr>
                                    <td class="font-medium text-slate-900">
                                        <a href="{{ route('nodes.show', $node) }}" class="hover:text-brand-700">{{ $node->hostname }}</a>
                                    </td>
                                    <td><x-badge dot :color="$statusColor">{{ ucfirst($status) }}</x-badge></td>
                                    <td>
                                        @if ($node->servicesHealthy())
                                            <x-badge color="success">All OK</x-badge>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach (['postfix_ok' => 'Postfix', 'rspamd_ok' => 'Rspamd', 'clamav_ok' => 'ClamAV'] as $field => $label)
                                                    @unless ($node->$field)
                                                        <x-badge color="danger">{{ $label }}</x-badge>
                                                    @endunless
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right tabular">{{ number_format($node->queue_depth) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.app>
