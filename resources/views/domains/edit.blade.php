<x-layouts.app :title="'Edit ' . $domain->name">
    <x-page-header :title="'Edit ' . $domain->name" icon="server"
        :back="route('domains.show', $domain)" />

    <form method="POST" action="{{ route('domains.update', $domain) }}">
        @csrf
        @method('PUT')
        @include('domains._form')

        <div class="mt-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-button type="submit" icon="check">Save Changes</x-button>
                <x-button href="{{ route('domains.show', $domain) }}" variant="secondary">Cancel</x-button>
            </div>
            <x-delete-button name="del-domain" :action="route('domains.destroy', $domain)"
                title="Delete Domain?"
                message="Its mailboxes, quarantine, mail log and rules are deleted too. Mail will stop being filtered immediately." />
        </div>
    </form>
</x-layouts.app>
