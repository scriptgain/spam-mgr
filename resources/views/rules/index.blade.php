<x-layouts.app title="Allow / Block">
    <x-page-header title="Allow / Block" icon="lock"
        subtitle="Rules that override scoring. A rule reaches as wide as the scope you give it." />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card flush>
                <x-filter-bar>
                    <x-select name="list" class="w-40" onchange="this.form.submit()">
                        <option value="">All Lists</option>
                        @foreach (['allow' => 'Allow', 'block' => 'Block', 'spam_bypass' => 'Spam Bypass', 'rbl_bypass' => 'RBL Bypass'] as $l => $label)
                            <option value="{{ $l }}" @selected($list === $l)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="q" value="{{ $search }}" placeholder="Search values" class="w-52" />
                    <x-button type="submit" variant="secondary" size="sm">Search</x-button>
                </x-filter-bar>

                @if ($entries->isEmpty())
                    <x-empty-state icon="lock" title="No Rules Yet"
                        description="Add an allow or block rule to override what the scoring engine decides." />
                @else
                    <form method="POST" action="{{ route('rules.bulk') }}" x-data="{ selected: [] }">
                        @csrf
                        <div class="flex items-center justify-between gap-4 px-5 sm:px-6 py-3 border-b border-slate-100 bg-slate-50/60">
                            <p class="text-sm text-slate-500" x-text="selected.length ? selected.length + ' selected' : 'Select rules to remove them'"></p>
                            <div x-show="selected.length" x-cloak>
                                <x-button type="submit" size="sm" variant="danger" icon="trash">Remove</x-button>
                            </div>
                        </div>

                        <x-table flush>
                            <thead><tr><th class="w-10"></th><th>Value</th><th>Type</th><th>List</th><th>Scope</th><th class="text-right">Actions</th></tr></thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    @php
                                        $listColor = ['allow' => 'success', 'block' => 'danger'][$entry->list] ?? 'info';
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" value="{{ $entry->id }}" x-model="selected" class="rounded border-slate-300"></td>
                                        <td class="font-medium text-slate-900">{{ $entry->value }}</td>
                                        <td class="text-slate-500">{{ ucfirst($entry->type) }}</td>
                                        <td><x-badge :color="$listColor">{{ \Illuminate\Support\Str::headline($entry->list) }}</x-badge></td>
                                        <td class="text-slate-500">
                                            {{ $entry->reachLabel() }}@if ($entry->domain) ({{ $entry->domain->name }})@endif
                                        </td>
                                        <td class="text-right">
                                            <x-delete-button :name="'del-rule-' . $entry->id"
                                                :action="route('rules.destroy', $entry)"
                                                title="Remove Rule?" message="Mail matching it goes back to normal scoring." />
                                        </td>
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
        </div>

        <x-card title="Add A Rule">
            <form method="POST" action="{{ route('rules.store') }}" class="space-y-4">
                @csrf
                <x-field label="Scope" for="mail_domain_id"
                    hint="Leave unset to apply the rule everywhere you can.">
                    <x-select id="mail_domain_id" name="mail_domain_id">
                        <option value="">Everywhere</option>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain->id }}">{{ $domain->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Type" for="type" required>
                    <x-select id="type" name="type" required>
                        <option value="sender">Sender Address</option>
                        <option value="domain">Sender Domain</option>
                        <option value="ip">IP Or CIDR</option>
                    </x-select>
                </x-field>

                <x-field label="Value" for="value" required :error="$errors->first('value')">
                    <x-input id="value" name="value" placeholder="spammer@example.com" required />
                </x-field>

                <x-field label="List" for="list" required
                    hint="Spam bypass skips scoring but keeps virus scanning. RBL bypass skips blocklist checks only.">
                    <x-select id="list" name="list" required>
                        <option value="allow">Allow</option>
                        <option value="block">Block</option>
                        <option value="spam_bypass">Spam Bypass</option>
                        <option value="rbl_bypass">RBL Bypass</option>
                    </x-select>
                </x-field>

                <x-field label="Notes" for="notes">
                    <x-input id="notes" name="notes" placeholder="Why this rule exists" />
                </x-field>

                <x-button type="submit" icon="plus" class="w-full">Add Rule</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
