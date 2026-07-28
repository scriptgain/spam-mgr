@php $policy = $policy ?? null; @endphp
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <x-card title="Policy">
        <div class="space-y-4">
            <x-field label="Name" for="name" required :error="$errors->first('name')">
                <x-input id="name" name="name" value="{{ old('name', $policy?->name) }}" required />
            </x-field>

            <x-field label="Description" for="description" :error="$errors->first('description')">
                <textarea id="description" name="description" rows="2"
                    class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old('description', $policy?->description) }}</textarea>
            </x-field>

            <x-toggle name="is_default" :checked="(bool) old('is_default', $policy?->is_default ?? false)"
                label="Use As The Default"
                description="Domains with no policy of their own use this one. Only one policy can be the default." />
        </div>
    </x-card>

    <x-card title="Thresholds" subtitle="Rspamd scores. They must increase down the list.">
        <div class="space-y-4">
            <x-field label="Tag Level" for="tag_level" required
                hint="Adds a spam header. The recipient still gets the mail."
                :error="$errors->first('tag_level')">
                <x-input id="tag_level" name="tag_level" type="number" step="0.1" min="0" max="100"
                    value="{{ old('tag_level', $policy?->tag_level ?? 5.0) }}" required />
            </x-field>

            <x-field label="Subject Rewrite Level" for="tag2_level" required
                hint="Prefixes the subject so it is obvious in the inbox."
                :error="$errors->first('tag2_level')">
                <x-input id="tag2_level" name="tag2_level" type="number" step="0.1" min="0" max="100"
                    value="{{ old('tag2_level', $policy?->tag2_level ?? 8.0) }}" required />
            </x-field>

            <x-field label="Quarantine Level" for="kill_level" required
                hint="Holds the message. Set this too low and real mail gets held."
                :error="$errors->first('kill_level')">
                <x-input id="kill_level" name="kill_level" type="number" step="0.1" min="0" max="100"
                    value="{{ old('kill_level', $policy?->kill_level ?? 12.0) }}" required />
            </x-field>

            <x-toggle name="block_bulk" :checked="(bool) old('block_bulk', $policy?->block_bulk ?? false)"
                label="Block Bulk Mail" description="Treats newsletters and marketing sends as spam." />

            <x-toggle name="block_foreign_charset" :checked="(bool) old('block_foreign_charset', $policy?->block_foreign_charset ?? false)"
                label="Block Foreign Character Sets"
                description="Blunt: it will also block legitimate mail in other languages." />
        </div>
    </x-card>
</div>

<x-card title="Keyword Rules" class="mt-6" subtitle="One entry per line. Leave blank to skip.">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @foreach ([
            'subject_block_keywords' => ['Subject Blocks', 'Held if the subject contains any of these.'],
            'body_block_keywords' => ['Body Blocks', 'Held if the body contains any of these.'],
            'uri_allowlist' => ['URL Allowlist', 'Links to these hosts never count against the score.'],
        ] as $field => [$label, $hint])
            <x-field :label="$label" :for="$field" :hint="$hint">
                <textarea id="{{ $field }}" name="{{ $field }}" rows="6"
                    class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm font-mono text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500">{{ old($field, $policy?->$field) }}</textarea>
            </x-field>
        @endforeach
    </div>
</x-card>
