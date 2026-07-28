<x-layouts.app title="Add Policy">
    <x-page-header title="Add Policy" icon="filter" :back="route('policies.index')" />
    <form method="POST" action="{{ route('policies.store') }}">
        @csrf
        @include('policies._form', ['policy' => null])
        <div class="mt-6 flex items-center gap-3">
            <x-button type="submit" icon="check">Add Policy</x-button>
            <x-button href="{{ route('policies.index') }}" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
