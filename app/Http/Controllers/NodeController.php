<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\Node;
use App\Models\NodeBlacklistCheck;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MX filtering nodes. There is deliberately no limit and no metering: unlimited
 * nodes is the product's whole pitch, and each node is a machine the operator pays
 * for themselves.
 */
class NodeController extends Controller
{
    use ScopesToCustomer;

    /** RBLs checked per node. Kept short: each one is a live DNS lookup. */
    private const RBLS = [
        'zen.spamhaus.org',
        'bl.spamcop.net',
        'b.barracudacentral.org',
        'dnsbl.sorbs.net',
    ];

    public function index()
    {
        $this->ensureAdmin();

        return view('nodes.index', [
            'nodes' => Node::withCount('quarantined')->orderBy('hostname')->get(),
        ]);
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('nodes.create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $node = Node::create($this->validated($request));

        return redirect()->route('nodes.show', $node)
            ->with('status', "Node \"{$node->hostname}\" added. Enrol it to get its install command.");
    }

    public function show(Node $node)
    {
        $this->ensureAdmin();

        return view('nodes.show', [
            'node' => $node,
            'checks' => $node->blacklistChecks()->latest('checked_at')->limit(20)->get(),
            // Shown once, immediately after enrolment, then never again.
            'enrollmentToken' => session('enrollment_token'),
        ]);
    }

    public function edit(Node $node)
    {
        $this->ensureAdmin();

        return view('nodes.edit', compact('node'));
    }

    public function update(Request $request, Node $node)
    {
        $this->ensureAdmin();
        $node->update($this->validated($request, $node));

        return redirect()->route('nodes.show', $node)
            ->with('status', "Node \"{$node->hostname}\" updated.");
    }

    public function destroy(Node $node)
    {
        $this->ensureAdmin();
        $hostname = $node->hostname;
        $node->delete();

        return redirect()->route('nodes.index')->with('status', "Node \"{$hostname}\" deleted.");
    }

    /**
     * Issue a one-time enrolment token. The plaintext is flashed to the session and
     * shown once; only its hash is stored, so a leaked panel database does not hand
     * over working node credentials.
     */
    public function enroll(Node $node)
    {
        $this->ensureAdmin();
        $token = $node->issueEnrollmentToken();

        return redirect()->route('nodes.show', $node)
            ->with('enrollment_token', $token)
            ->with('status', 'Enrolment token issued. Copy it now, it is not shown again.');
    }

    /**
     * A listed MX node silently loses relay to some destinations, so this is
     * checked deliberately rather than discovered from a customer complaint.
     */
    public function checkBlacklists(Node $node)
    {
        $this->ensureAdmin();

        if (! $node->ip) {
            return back()->with('status', "Node \"{$node->hostname}\" has no IP recorded yet, so it cannot be checked.");
        }

        $reversed = implode('.', array_reverse(explode('.', $node->ip)));
        $listed = 0;

        foreach (self::RBLS as $rbl) {
            $answer = @dns_get_record("{$reversed}.{$rbl}", DNS_A);
            $isListed = ! empty($answer);
            $listed += $isListed ? 1 : 0;

            $detail = null;
            if ($isListed) {
                $txt = @dns_get_record("{$reversed}.{$rbl}", DNS_TXT);
                $detail = $txt[0]['txt'] ?? null;
            }

            NodeBlacklistCheck::create([
                'node_id' => $node->id,
                'rbl' => $rbl,
                'status' => $isListed ? 'listed' : 'clear',
                'detail' => $detail,
                'checked_at' => now(),
            ]);
        }

        return back()->with('status', $listed === 0
            ? "{$node->hostname} is clear on all " . count(self::RBLS) . ' lists checked.'
            : "{$node->hostname} is LISTED on {$listed} of " . count(self::RBLS) . ' lists. Delivery will be affected.');
    }

    private function validated(Request $request, ?Node $node = null): array
    {
        return $request->validate([
            'hostname' => [
                'required', 'string', 'max:191',
                Rule::unique('nodes', 'hostname')->ignore($node?->id),
            ],
            'ip' => ['nullable', 'ip'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'active' => ['nullable', 'boolean'],
        ]);
    }
}
