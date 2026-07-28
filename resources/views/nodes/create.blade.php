<x-layouts.app title="Add Node">
    <x-page-header title="Add Node" icon="cloud"
        subtitle="Register the node here, then enrol it to get a one-time install command."
        :back="route('nodes.index')" />

    <form method="POST" action="{{ route('nodes.store') }}">
        @csrf
        @include('nodes._form', ['node' => null])
        <div class="mt-6 flex items-center gap-3">
            <x-button type="submit" icon="check">Add Node</x-button>
            <x-button href="{{ route('nodes.index') }}" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
