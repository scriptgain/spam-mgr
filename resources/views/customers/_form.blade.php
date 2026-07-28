@php $customer = $customer ?? null; @endphp

{{-- Full-width 2-column form: fields plus a help sidebar. Never a narrow centred
     column, per the house full-width rule. --}}
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <x-card title="Customer">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Name" for="name" required class="sm:col-span-2" :error="$errors->first('name')">
                    <x-input id="name" name="name" value="{{ old('name', $customer?->name) }}" required />
                </x-field>

                <x-field label="Contact Email" for="contact_email"
                    hint="Where digests and notifications go, if you enable them."
                    :error="$errors->first('contact_email')">
                    <x-input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $customer?->contact_email) }}" />
                </x-field>

                <x-field label="Phone" for="phone" :error="$errors->first('phone')">
                    <x-input id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" />
                </x-field>

                <x-field label="Notes" for="notes" class="sm:col-span-2" :error="$errors->first('notes')">
                    <textarea id="notes" name="notes" rows="4"
                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('notes', $customer?->notes) }}</textarea>
                </x-field>

                <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                    <x-toggle name="active" :checked="(bool) old('active', $customer?->active ?? true)"
                        label="Customer Active"
                        description="Inactive customers cannot be assigned new domains." />
                </div>
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="What A Customer Is">
            <p class="text-sm text-slate-600">
                A customer is whoever you filter mail for: one of your clients, or a
                department if this install serves a single organisation.
            </p>
            <p class="mt-3 text-sm text-slate-600">
                Domains, mailboxes, quarantine and rules all hang off a customer, and a
                portal login only ever sees its own customer's mail.
            </p>
        </x-card>

        <x-card title="Next Steps">
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 mt-0.5 text-emerald-600 shrink-0" /> Add the domains you filter for them.</li>
                <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 mt-0.5 text-emerald-600 shrink-0" /> Point those domains' MX records at your nodes.</li>
                <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 mt-0.5 text-emerald-600 shrink-0" /> Create a portal login so they can clear their own quarantine.</li>
            </ul>
        </x-card>
    </div>
</div>
