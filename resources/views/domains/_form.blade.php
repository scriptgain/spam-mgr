{{-- Shared by create + edit. $domain is null when creating. --}}
@php $domain = $domain ?? null; @endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <x-card title="Domain">
        <div class="space-y-4">
            <x-field label="Domain Name" for="name" required
                hint="The domain whose mail you filter, for example example.com."
                :error="$errors->first('name')">
                <x-input id="name" name="name" value="{{ old('name', $domain?->name) }}"
                    placeholder="example.com" required @if($domain) readonly @endif />
                @if ($domain)
                    <p class="mt-1 text-xs text-slate-500">The name cannot be changed. Delete and re-add to rename.</p>
                @endif
            </x-field>

            <x-field label="Customer" for="customer_id" required
                hint="Who this domain belongs to. Their portal users see only their own mail."
                :error="$errors->first('customer_id')">
                <x-select id="customer_id" name="customer_id" required>
                    <option value="">Choose a customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', $domain?->customer_id) == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Filtering Policy" for="spam_policy_id"
                hint="Leave unset to use the system default policy."
                :error="$errors->first('spam_policy_id')">
                <x-select id="spam_policy_id" name="spam_policy_id">
                    <option value="">Use The Default</option>
                    @foreach ($policies as $policy)
                        <option value="{{ $policy->id }}" @selected(old('spam_policy_id', $domain?->spam_policy_id) == $policy->id)>
                            {{ $policy->name }}@if ($policy->is_default) (default)@endif
                        </option>
                    @endforeach
                </x-select>
            </x-field>
        </div>
    </x-card>

    <x-card title="Delivery" subtitle="Where clean mail is relayed after filtering.">
        <div class="space-y-4">
            <x-field label="Destination Host" for="destination_host"
                hint="The real mail server behind the gateway. A hostname or IP."
                :error="$errors->first('destination_host')">
                <x-input id="destination_host" name="destination_host"
                    value="{{ old('destination_host', $domain?->destination_host) }}"
                    placeholder="mail.example.com" />
            </x-field>

            <x-field label="Destination Port" for="destination_port" required
                :error="$errors->first('destination_port')">
                <x-input id="destination_port" name="destination_port" type="number" min="1" max="65535"
                    value="{{ old('destination_port', $domain?->destination_port ?? 25) }}" required />
            </x-field>

            <x-field label="TLS Policy" for="tls_policy" required
                hint="Enforced refuses to deliver without TLS. Safer, but it will bounce mail if the destination has no working certificate."
                :error="$errors->first('tls_policy')">
                <x-select id="tls_policy" name="tls_policy" required>
                    @foreach (['opportunistic' => 'Opportunistic (use TLS when offered)', 'enforced' => 'Enforced (require TLS)', 'none' => 'None (plaintext)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('tls_policy', $domain?->tls_policy ?? 'opportunistic') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field label="Recipient Mode" for="recipient_mode" required
                hint="Known mailboxes only rejects unknown addresses at SMTP time, which stops dictionary attacks before they cost anything."
                :error="$errors->first('recipient_mode')">
                <x-select id="recipient_mode" name="recipient_mode" required>
                    <option value="list" @selected(old('recipient_mode', $domain?->recipient_mode ?? 'list') === 'list')>Known Mailboxes Only</option>
                    <option value="all" @selected(old('recipient_mode', $domain?->recipient_mode) === 'all')>Accept All Addresses</option>
                </x-select>
            </x-field>

            <x-toggle name="active" :checked="(bool) old('active', $domain?->active ?? true)"
                label="Filtering Active"
                description="Turning this off stops the nodes accepting mail for the domain." />
        </div>
    </x-card>
</div>
