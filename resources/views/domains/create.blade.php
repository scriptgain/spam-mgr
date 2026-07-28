<x-layouts.app title="Add Domain">
    <x-page-header title="Add Domain" icon="server"
        subtitle="Add the domain, point its MX records at your nodes, then verify ownership."
        :back="route('domains.index')" />

    <form method="POST" action="{{ route('domains.store') }}">
        @csrf
        @include('domains._form', ['domain' => null])

        <div class="mt-6 flex items-center gap-3">
            <x-button type="submit" icon="check">Add Domain</x-button>
            <x-button href="{{ route('domains.index') }}" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
