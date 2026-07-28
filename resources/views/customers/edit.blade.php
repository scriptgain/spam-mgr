<x-layouts.app :title="'Edit ' . $customer->name">
    <x-page-header :title="'Edit ' . $customer->name" icon="users" :back="route('customers.show', $customer)" />
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf
        @method('PUT')
        @include('customers._form')
        <div class="mt-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-button type="submit" icon="check">Save Changes</x-button>
                <x-button href="{{ route('customers.show', $customer) }}" variant="secondary">Cancel</x-button>
            </div>
            <x-delete-button name="del-customer" :action="route('customers.destroy', $customer)"
                title="Delete Customer?"
                message="Their domains, mailboxes, quarantine and rules are deleted. Portal logins are kept but unlinked." />
        </div>
    </form>
</x-layouts.app>
