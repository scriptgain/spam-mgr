@php $node = $node ?? null; @endphp

{{-- Full-width 2-column form: fields plus a help sidebar. Never a narrow centred
     column, per the house full-width rule. --}}
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <x-card title="Node">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Hostname" for="hostname" required
                    hint="The node's MX FQDN, for example mx1.example.com. This is what your customers' MX records point at."
                    :error="$errors->first('hostname')">
                    <x-input id="hostname" name="hostname" value="{{ old('hostname', $node?->hostname) }}"
                        placeholder="mx1.example.com" required />
                </x-field>

                <x-field label="IP Address" for="ip"
                    hint="Used for blocklist checks. The node reports its own IP when it enrols, so you can leave this blank."
                    :error="$errors->first('ip')">
                    <x-input id="ip" name="ip" value="{{ old('ip', $node?->ip) }}" placeholder="203.0.113.10" />
                </x-field>

                <x-field label="Notes" for="notes" class="sm:col-span-2" :error="$errors->first('notes')">
                    <textarea id="notes" name="notes" rows="4"
                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes', $node?->notes) }}</textarea>
                </x-field>

                <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                    <x-toggle name="active" :checked="(bool) old('active', $node?->active ?? true)"
                        label="Node Active"
                        description="Inactive nodes stop receiving configuration and are excluded from MX checks." />
                </div>
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="Unlimited Nodes">
            <p class="text-sm text-slate-600">
                Add as many as you want. Node count is never metered or licensed, because
                every node is a machine you already pay for.
            </p>
            <p class="mt-3 text-sm text-slate-600">
                Point <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">mx1</code>,
                <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">mx2</code> and so on
                at them for redundancy. Every node reports into this panel.
            </p>
        </x-card>

        <x-card title="After You Save">
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 mt-0.5 text-emerald-600 shrink-0" /> Issue an enrolment token from the node's page.</li>
                <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 mt-0.5 text-emerald-600 shrink-0" /> Run the install command on the machine.</li>
                <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 mt-0.5 text-emerald-600 shrink-0" /> Set its A and PTR records, then add it as an MX.</li>
            </ul>
            <p class="mt-4 text-xs text-slate-500">
                The token works once and is shown once. Only its hash is stored.
            </p>
        </x-card>
    </div>
</div>
