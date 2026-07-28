<x-layouts.app title="Add Customer">
    <x-page-header title="Add Customer" icon="users" :back="route('customers.index')" />
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        @include('customers._form', ['customer' => null])
        <div class="mt-6 flex items-center gap-3">
            <x-button type="submit" icon="check">Add Customer</x-button>
            <x-button href="{{ route('customers.index') }}" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
