<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\MailDomain;
use App\Models\QuarantineMessage;
use Illuminate\Http\Request;

/**
 * Held mail.
 *
 * Releasing does not deliver anything here: it marks the message and the holding
 * node picks it up on its next poll. The panel never needs inbound access to a
 * node, which is what lets nodes sit anywhere without opening a port.
 */
class QuarantineController extends Controller
{
    use ScopesToCustomer;

    public function index(Request $request)
    {
        $query = QuarantineMessage::with(['domain', 'node'])
            ->where('verdict', 'quarantined');

        $this->scopeThroughDomain($query);

        if ($domainId = $request->get('domain')) {
            $query->where('mail_domain_id', $domainId);
        }
        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('sender', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        return view('quarantine.index', [
            'messages' => $query->latest('quarantined_at')->paginate(50)->withQueryString(),
            'domains' => $this->viewerDomains(),
            'search' => $search,
            'domainId' => $domainId,
        ]);
    }

    public function show(QuarantineMessage $message)
    {
        $this->authorizeMessage($message);
        $message->load(['domain', 'node']);

        return view('quarantine.show', compact('message'));
    }

    public function release(QuarantineMessage $message)
    {
        $this->authorizeMessage($message);
        $message->markReleased();

        return back()->with('status', 'Queued for release. The holding node will deliver it on its next poll.');
    }

    public function destroy(QuarantineMessage $message)
    {
        $this->authorizeMessage($message);
        $message->update(['verdict' => 'deleted']);

        return back()->with('status', 'Message deleted from quarantine.');
    }

    /**
     * Bulk release or delete. Re-scoped through the domain so a portal user cannot
     * act on another customer's mail by posting arbitrary ids.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:release,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = QuarantineMessage::whereIn('id', $data['ids']);
        $this->scopeThroughDomain($query);
        $messages = $query->get();

        foreach ($messages as $message) {
            $data['action'] === 'release'
                ? $message->markReleased()
                : $message->update(['verdict' => 'deleted']);
        }

        $verb = $data['action'] === 'release' ? 'queued for release' : 'deleted';

        return back()->with('status', $messages->count() . " message(s) {$verb}.");
    }

    private function authorizeMessage(QuarantineMessage $message): void
    {
        $this->authorizeCustomer($message->domain?->customer_id);
    }

    private function viewerDomains()
    {
        $query = MailDomain::query()->orderBy('name');
        $this->scopeToViewer($query);

        return $query->get(['id', 'name']);
    }
}
