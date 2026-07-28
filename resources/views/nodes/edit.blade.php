<x-layouts.app :title="'Edit ' . $node->hostname">
    <x-page-header :title="'Edit ' . $node->hostname" icon="cloud" :back="route('nodes.show', $node)" />

    <form method="POST" action="{{ route('nodes.update', $node) }}">
        @csrf
        @method('PUT')
        @include('nodes._form')
        <div class="mt-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-button type="submit" icon="check">Save Changes</x-button>
                <x-button href="{{ route('nodes.show', $node) }}" variant="secondary">Cancel</x-button>
            </div>
            <x-delete-button name="del-node" :action="route('nodes.destroy', $node)"
                title="Delete Node?"
                message="Mail already held on this node becomes unreleasable. Drain it from your MX records first." />
        </div>
    </form>
</x-layouts.app>
