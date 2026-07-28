<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\MailDomain;
use App\Models\MailLogEntry;
use Illuminate\Http\Request;

class MailLogController extends Controller
{
    use ScopesToCustomer;

    public function index(Request $request)
    {
        $query = MailLogEntry::with(['domain', 'node']);

        $this->scopeThroughDomain($query);

        if ($domainId = $request->get('domain')) {
            $query->where('mail_domain_id', $domainId);
        }
        if ($verdict = $request->get('verdict')) {
            $query->where('verdict', $verdict);
        }
        if ($delivery = $request->get('delivery')) {
            $query->where('delivery_status', $delivery);
        }
        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('sender', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $domainQuery = MailDomain::query()->orderBy('name');
        $this->scopeToViewer($domainQuery);

        return view('mail-log.index', [
            'entries' => $query->latest('logged_at')->paginate(100)->withQueryString(),
            'domains' => $domainQuery->get(['id', 'name']),
            'filters' => compact('domainId', 'verdict', 'delivery', 'search'),
        ]);
    }

    /**
     * Retry deferred mail. Clearing the status is all that is needed: the holding
     * node picks deferred entries back up on its next poll.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:retry,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = MailLogEntry::whereIn('id', $data['ids']);
        $this->scopeThroughDomain($query);

        if ($data['action'] === 'retry') {
            $count = (clone $query)->where('delivery_status', 'deferred')
                ->update(['delivery_status' => 'pending']);

            return back()->with('status', "{$count} deferred message(s) queued for retry.");
        }

        $count = $query->delete();

        return back()->with('status', "{$count} log entr(ies) deleted.");
    }
}
