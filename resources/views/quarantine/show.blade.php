<x-layouts.app title="Held Message">
    <x-page-header :title="$message->shortSubject(80)" icon="inbox"
        :subtitle="'Held ' . ($message->quarantined_at?->diffForHumans() ?? 'recently')"
        :back="route('quarantine.index')" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Message">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 shrink-0">From</dt>
                        <dd class="font-medium text-slate-900 text-right break-all">{{ $message->sender ?: 'Unknown' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 shrink-0">To</dt>
                        <dd class="font-medium text-slate-900 text-right break-all">{{ $message->recipient }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 shrink-0">Subject</dt>
                        <dd class="font-medium text-slate-900 text-right break-words">{{ $message->subject ?: '(no subject)' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 shrink-0">Domain</dt>
                        <dd class="font-medium text-slate-900 text-right">{{ $message->domain?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 shrink-0">Held By</dt>
                        <dd class="font-medium text-slate-900 text-right">{{ $message->node?->hostname ?? 'Unknown node' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Why It Was Held">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-2xl font-semibold text-slate-900 tabular">
                        {{ $message->spam_score !== null ? number_format($message->spam_score, 1) : 'n/a' }}
                    </span>
                    <span class="text-sm text-slate-500">spam score</span>
                </div>
                <p class="text-sm text-slate-600">{{ $message->reason ?: 'No detail recorded.' }}</p>
                <p class="mt-4 text-xs text-slate-500">
                    The message body stays on the node that caught it and is never copied into this panel.
                </p>
            </x-card>
        </div>

        <x-card title="Actions">
            <div class="space-y-3">
                <form method="POST" action="{{ route('quarantine.release', $message) }}">
                    @csrf
                    <x-button type="submit" icon="check" class="w-full">Release To Recipient</x-button>
                </form>
                <p class="text-xs text-slate-500">
                    Releasing marks the message. The node holding it delivers on its next poll, usually within a minute.
                </p>

                <div class="pt-3 border-t border-slate-100">
                    <x-delete-button name="del-message" :action="route('quarantine.destroy', $message)"
                        title="Delete Message?"
                        message="It will not be delivered and the node will purge the body." />
                </div>
            </div>

            @if ($message->release_error)
                <x-slot:footer>
                    <p class="text-xs text-rose-600">
                        Last release attempt failed: {{ $message->release_error }}
                        ({{ $message->release_attempts }} attempt(s))
                    </p>
                </x-slot:footer>
            @endif
        </x-card>
    </div>
</x-layouts.app>
