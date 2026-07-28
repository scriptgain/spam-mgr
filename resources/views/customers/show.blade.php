<x-layouts.app :title="$customer->name">
    <x-page-header :title="$customer->name" icon="users"
        :subtitle="$customer->contact_email ?: 'No contact email'" :back="route('customers.index')">
        <x-slot:actions>
            <x-button href="{{ route('customers.edit', $customer) }}" icon="edit" variant="secondary" size="sm">Edit</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Domains" flush>
            @if ($customer->domains->isEmpty())
                <x-empty-state icon="server" title="No Domains"
                    description="Add a domain for this customer to start filtering." />
            @else
                <x-table flush>
                    <thead><tr><th>Domain</th><th>Policy</th><th>Verified</th></tr></thead>
                    <tbody>
                        @foreach ($customer->domains as $domain)
                            <tr>
                                <td class="font-medium text-slate-900">
                                    <a href="{{ route('domains.show', $domain) }}" class="hover:text-brand-700">{{ $domain->name }}</a>
                                </td>
                                <td class="text-slate-500">{{ $domain->policy?->name ?? 'Default' }}</td>
                                <td>
                                    <x-badge :color="$domain->isVerified() ? 'success' : 'warn'" dot>
                                        {{ $domain->isVerified() ? 'Verified' : 'Pending' }}
                                    </x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>

        <x-card title="Portal Users" subtitle="These logins see only this customer's mail." flush>
            @if ($customer->users->isEmpty())
                <x-empty-state icon="users" title="No Portal Users"
                    description="Create a user with the Customer role and assign them here." />
            @else
                <x-table flush>
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                    <tbody>
                        @foreach ($customer->users as $user)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $user->name }}</td>
                                <td class="text-slate-500">{{ $user->email }}</td>
                                <td><x-badge color="neutral">{{ ucfirst($user->role) }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </x-card>
    </div>

    @if ($customer->notes)
        <x-card title="Notes" class="mt-6">
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $customer->notes }}</p>
        </x-card>
    @endif
</x-layouts.app>
