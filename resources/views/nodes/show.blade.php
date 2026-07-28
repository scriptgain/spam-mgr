<x-layouts.app :title="$node->hostname">
    @php
        $status = $node->displayStatus();
        $statusColor = ['online' => 'success', 'stale' => 'warn'][$status] ?? 'neutral';
    @endphp

    <x-page-header :title="$node->hostname" icon="cloud"
        subtitle="MX filtering node" :back="route('nodes.index')">
        <x-slot:actions>
            <x-button href="{{ route('nodes.edit', $node) }}" icon="edit" variant="secondary" size="sm">Edit</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($enrollmentToken)
        <x-alert type="success" class="mb-6" title="Enrolment Token Issued">
            <p>Run this on the node. The token works once and is not shown again.</p>
            <code class="mt-2 block rounded bg-white/70 px-3 py-2 text-xs font-mono ring-1 ring-inset ring-emerald-200 break-all">curl -fsSL {{ url('/install-node.sh') }} | sudo bash -s -- --panel {{ url('/') }} --token {{ $enrollmentToken }} --hostname {{ $node->hostname }}</code>
        </x-alert>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <x-stat label="Status" :value="ucfirst($status)" icon="cloud" />
        <x-stat label="Queue Depth" :value="number_format($node->queue_depth)" icon="clock"
            :trend="$node->queue_depth > 100 ? 'Backing Up' : null" trend-color="danger" />
        <x-stat label="Disk Used" :value="$node->disk_percent . '%'" icon="database"
            :trend="$node->disk_percent > 85 ? 'Low Space' : null" trend-color="danger" />
        <x-stat label="Load" :value="number_format($node->load, 2)" icon="bolt" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Health" subtitle="A port check on :25 stays green while these are broken.">
            <dl class="space-y-3 text-sm">
                @foreach (['postfix_ok' => 'Postfix', 'rspamd_ok' => 'Rspamd (scoring)', 'clamav_ok' => 'ClamAV (virus)'] as $field => $label)
                    <div class="flex justify-between gap-4 items-center">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd><x-badge :color="$node->$field ? 'success' : 'danger'" dot>{{ $node->$field ? 'OK' : 'Down' }}</x-badge></dd>
                    </div>
                @endforeach
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">Certificate Expires</dt>
                    <dd class="font-medium text-slate-900">{{ $node->cert_expires_at?->toFormattedDateString() ?? 'Unknown' }}</dd>
                </div>
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">Last Seen</dt>
                    <dd class="font-medium text-slate-900">{{ $node->last_seen_at?->diffForHumans() ?? 'Never' }}</dd>
                </div>
                <div class="flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">Agent Version</dt>
                    <dd class="font-medium text-slate-900">{{ $node->agent_version ?: 'Not enrolled' }}</dd>
                </div>
            </dl>

            <x-slot:footer>
                <form method="POST" action="{{ route('nodes.enroll', $node) }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" icon="key">
                        {{ $node->last_seen_at ? 'Re-Issue Enrolment Token' : 'Issue Enrolment Token' }}
                    </x-button>
                </form>
            </x-slot:footer>
        </x-card>

        <x-card title="Blocklist Checks" subtitle="A listed node silently loses delivery to some destinations." flush>
            @if ($checks->isEmpty())
                <x-empty-state icon="shield" title="Not Checked Yet"
                    description="Run a check to see whether this node's IP is on a public blocklist." />
            @else
                <x-table flush>
                    <thead><tr><th>Blocklist</th><th>Result</th><th>Checked</th></tr></thead>
                    <tbody>
                        @foreach ($checks as $check)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $check->rbl }}</td>
                                <td>
                                    <x-badge :color="$check->isListed() ? 'danger' : 'success'" dot>
                                        {{ $check->isListed() ? 'Listed' : 'Clear' }}
                                    </x-badge>
                                </td>
                                <td class="text-slate-500">{{ $check->checked_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif

            <x-slot:footer>
                <form method="POST" action="{{ route('nodes.check-blacklists', $node) }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" icon="refresh">Check Blocklists Now</x-button>
                </form>
            </x-slot:footer>
        </x-card>
    </div>
</x-layouts.app>
