<x-layouts.app title="Customers">
    <x-page-header title="Customers" icon="users"
        subtitle="Who you filter mail for. Each customer's portal users see only their own domains and quarantine.">
        <x-slot:actions>
            <x-button href="{{ route('customers.create') }}" icon="plus" size="sm">Add Customer</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        @if ($customers->isEmpty())
            <x-empty-state icon="users" title="No Customers Yet"
                description="Add a customer, then add the domains you filter for them." />
        @else
            <x-table flush>
                <thead>
                    <tr><th>Name</th><th>Contact</th><th class="text-right">Domains</th><th class="text-right">Mailboxes</th><th class="text-right">Portal Users</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('customers.show', $customer) }}" class="hover:text-brand-700">{{ $customer->name }}</a>
                            </td>
                            <td class="text-slate-500">{{ $customer->contact_email ?: 'None' }}</td>
                            <td class="text-right tabular">{{ number_format($customer->domains_count) }}</td>
                            <td class="text-right tabular">{{ number_format($customer->recipients_count) }}</td>
                            <td class="text-right tabular">{{ number_format($customer->users_count) }}</td>
                            <td><x-badge :color="$customer->active ? 'success' : 'neutral'">{{ $customer->active ? 'Active' : 'Inactive' }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</x-layouts.app>
