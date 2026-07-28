<x-layouts.app :title="'Edit ' . $policy->name">
    <x-page-header :title="'Edit ' . $policy->name" icon="filter" :back="route('policies.index')" />
    <form method="POST" action="{{ route('policies.update', $policy) }}">
        @csrf
        @method('PUT')
        @include('policies._form')
        <div class="mt-6 flex items-center gap-3">
            <x-button type="submit" icon="check">Save Changes</x-button>
            <x-button href="{{ route('policies.index') }}" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
