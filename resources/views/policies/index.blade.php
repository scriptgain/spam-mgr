<x-layouts.app title="Filtering Policies">
    <x-page-header title="Filtering Policies" icon="filter"
        subtitle="Score thresholds your nodes enforce. Tag adds a header, subject rewrite marks it, quarantine holds it.">
        <x-slot:actions>
            <x-button href="{{ route('policies.create') }}" icon="plus" size="sm">Add Policy</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card flush>
        <x-table flush>
            <thead>
                <tr><th>Name</th><th class="text-right">Tag</th><th class="text-right">Subject Rewrite</th><th class="text-right">Quarantine</th><th class="text-right">Domains</th><th>Default</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($policies as $policy)
                    <tr>
                        <td class="font-medium text-slate-900">
                            {{ $policy->name }}
                            @if ($policy->description)
                                <p class="text-xs text-slate-500 font-normal mt-0.5">{{ $policy->description }}</p>
                            @endif
                        </td>
                        <td class="text-right tabular">{{ number_format($policy->tag_level, 1) }}</td>
                        <td class="text-right tabular">{{ number_format($policy->tag2_level, 1) }}</td>
                        <td class="text-right tabular">{{ number_format($policy->kill_level, 1) }}</td>
                        <td class="text-right tabular">{{ number_format($policy->domains_count) }}</td>
                        <td>@if ($policy->is_default)<x-badge color="info">Default</x-badge>@endif</td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-2">
                                <x-icon-button :href="route('policies.edit', $policy)" icon="edit" title="Edit" />
                                @unless ($policy->is_default)
                                    <x-delete-button :name="'del-policy-' . $policy->id"
                                        :action="route('policies.destroy', $policy)"
                                        title="Delete Policy?" message="Domains using it fall back to the default policy." />
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
</x-layouts.app>
