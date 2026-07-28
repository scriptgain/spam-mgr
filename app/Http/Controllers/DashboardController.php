<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\Customer;
use App\Models\MailDomain;
use App\Models\MailLogEntry;
use App\Models\MailRecipient;
use App\Models\Node;
use App\Models\QuarantineMessage;

class DashboardController extends Controller
{
    use ScopesToCustomer;

    public function __invoke()
    {
        $customerId = $this->viewerCustomerId();
        $since = now()->subDay();

        // Verdict mix over the last 24h, in one pass rather than four counts.
        $verdicts = $this->scopeThroughDomain(MailLogEntry::query())
            ->where('logged_at', '>=', $since)
            ->selectRaw('verdict, count(*) as total')
            ->groupBy('verdict')
            ->pluck('total', 'verdict');

        $handled = (int) $verdicts->sum();
        $blocked = (int) ($verdicts['quarantined'] ?? 0) + (int) ($verdicts['rejected'] ?? 0);

        return view('dashboard', [
            'isOperator' => $customerId === null,

            'domains' => $this->scopeToViewer(MailDomain::query())->where('active', true)->count(),
            'mailboxes' => $this->scopeToViewer(MailRecipient::query())->where('active', true)->count(),
            'customers' => $customerId === null ? Customer::where('active', true)->count() : null,

            'handled24h' => $handled,
            'blocked24h' => $blocked,
            // The number that justifies the product's existence, so it leads.
            'blockedPercent' => $handled > 0 ? round($blocked / $handled * 100, 1) : 0.0,
            'deferred' => $this->scopeThroughDomain(MailLogEntry::query())
                ->where('delivery_status', 'deferred')->count(),

            'heldTotal' => $this->scopeThroughDomain(QuarantineMessage::query())
                ->where('verdict', 'quarantined')->count(),
            'heldRecent' => $this->scopeThroughDomain(QuarantineMessage::query())
                ->where('verdict', 'quarantined')
                ->with('domain:id,name')
                ->latest('quarantined_at')
                ->limit(10)
                ->get(),

            // Node health is the operator's problem, not a customer's.
            'nodes' => $customerId === null
                ? Node::where('active', true)->orderBy('hostname')->get()
                : collect(),

            'unverified' => $customerId === null
                ? MailDomain::whereNull('verified_at')->where('active', true)->count()
                : 0,
        ]);
    }
}
