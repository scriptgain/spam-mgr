<x-layouts.app title="MX Nodes">
    <x-page-header title="MX Nodes" icon="cloud"
        subtitle="Your filtering gateways. There is no limit: run as many as you want and point mx1, mx2 and so on at them.">
        <x-slot:actions>
            <x-button href="{{ route('nodes.create') }}" icon="plus" size="sm">Add Node</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        @if ($nodes->isEmpty())
            <x-empty-state icon="cloud" title="No Nodes Yet"
                description="Add your first MX node, enrol it, and run the installer on the machine." />
        @else
            <x-table flush>
                <thead>
                    <tr>
                        <th>Hostname</th><th>IP</th><th>Status</th><th>Services</th>
                        <th class="text-right">Queue</th><th class="text-right">Disk</th>
                        <th>Last Seen</th><th class="text-right">Actions</th>
                    </tr>
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
                            <td class="text-slate-500 tabular">{{ $node->ip ?: 'Unknown' }}</td>
                            <td><x-badge dot :color="$statusColor">{{ ucfirst($status) }}</x-badge></td>
                            <td>
                                @if ($node->servicesHealthy())
                                    <x-badge color="success">All OK</x-badge>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (['postfix_ok' => 'Postfix', 'rspamd_ok' => 'Rspamd', 'clamav_ok' => 'ClamAV'] as $field => $label)
                                            @unless ($node->$field)<x-badge color="danger">{{ $label }}</x-badge>@endunless
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-right tabular">{{ number_format($node->queue_depth) }}</td>
                            <td class="text-right tabular">{{ $node->disk_percent }}%</td>
                            <td class="text-slate-500">{{ $node->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-2">
                                    <x-icon-button :href="route('nodes.show', $node)" icon="eye" title="View" />
                                    <x-icon-button :href="route('nodes.edit', $node)" icon="edit" title="Edit" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</x-layouts.app>
