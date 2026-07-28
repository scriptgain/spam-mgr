<x-layouts.app title="Mailboxes">
    <x-page-header title="Mailboxes" icon="users"
        subtitle="The addresses your nodes accept mail for. Unknown addresses are rejected at SMTP time, which stops dictionary attacks." />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card flush>
                <x-filter-bar>
                    <x-select name="domain" class="w-44" onchange="this.form.submit()">
                        <option value="">All Domains</option>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain->id }}" @selected($domainId == $domain->id)>{{ $domain->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="q" value="{{ $search }}" placeholder="Search addresses" class="w-52" />
                    <x-button type="submit" variant="secondary" size="sm">Search</x-button>
                </x-filter-bar>

                @if ($recipients->isEmpty())
                    <x-empty-state icon="users" title="No Mailboxes Yet"
                        description="Add addresses so your nodes know which recipients are real." />
                @else
                    <x-table flush>
                        <thead><tr><th>Address</th><th>Domain</th><th>Spam Filtering</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($recipients as $recipient)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $recipient->address }}</td>
                                    <td class="text-slate-500">{{ $recipient->domain?->name }}</td>
                                    <td>
                                        <x-badge :color="$recipient->filtering_enabled ? 'success' : 'warn'">
                                            {{ $recipient->filtering_enabled ? 'On' : 'Bypassed' }}
                                        </x-badge>
                                    </td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <form method="POST" action="{{ route('mailboxes.toggle', $recipient) }}">
                                                @csrf
                                                <x-icon-button type="submit" icon="sync"
                                                    :title="$recipient->filtering_enabled ? 'Bypass spam filtering' : 'Enable spam filtering'" />
                                            </form>
                                            <x-delete-button :name="'del-mb-' . $recipient->id"
                                                :action="route('mailboxes.destroy', $recipient)"
                                                title="Remove Mailbox?"
                                                message="Mail to this address will be rejected if the domain only accepts known mailboxes." />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif

                @if ($recipients->hasPages())
                    <x-slot:footer>{{ $recipients->links() }}</x-slot:footer>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Add One">
                <form method="POST" action="{{ route('mailboxes.store') }}" class="space-y-4">
                    @csrf
                    <x-field label="Domain" for="mail_domain_id" required>
                        <x-select id="mail_domain_id" name="mail_domain_id" required>
                            @foreach ($domains as $domain)
                                <option value="{{ $domain->id }}" @selected($domainId == $domain->id)>{{ $domain->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Address" for="address" required :error="$errors->first('address')">
                        <x-input id="address" name="address" type="email" placeholder="bob@example.com" required />
                    </x-field>
                    <x-button type="submit" icon="plus" class="w-full">Add Mailbox</x-button>
                </form>
            </x-card>

            <x-card title="Import A List" subtitle="Paste addresses, one per line.">
                <form method="POST" action="{{ route('mailboxes.bulk') }}" class="space-y-4">
                    @csrf
                    <x-field label="Domain" for="bulk_domain" required>
                        <x-select id="bulk_domain" name="mail_domain_id" required>
                            @foreach ($domains as $domain)
                                <option value="{{ $domain->id }}" @selected($domainId == $domain->id)>{{ $domain->name }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="Addresses" for="addresses" required
                        hint="Anything on the wrong domain or already present is skipped.">
                        <textarea id="addresses" name="addresses" rows="6" required
                            placeholder="bob@example.com&#10;sue@example.com"
                            class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>
                    </x-field>
                    <x-button type="submit" variant="secondary" icon="plus" class="w-full">Import</x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.app>
