<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\Customer;
use App\Models\MailDomain;
use App\Models\SpamPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MailDomainController extends Controller
{
    use ScopesToCustomer;

    public function index(Request $request)
    {
        $query = MailDomain::with(['customer', 'policy'])->withCount('recipients');

        $this->scopeToViewer($query);

        if ($search = trim((string) $request->get('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('domains.index', [
            'domains' => $query->orderBy('name')->paginate(50)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('domains.create', [
            'customers' => Customer::where('active', true)->orderBy('name')->get(),
            'policies' => SpamPolicy::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $domain = MailDomain::create($this->validated($request));

        return redirect()->route('domains.show', $domain)
            ->with('status', "Domain \"{$domain->name}\" added. Point its MX records at your nodes, then verify.");
    }

    public function show(MailDomain $domain)
    {
        $this->authorizeCustomer($domain->customer_id);
        $domain->load(['customer', 'policy']);

        return view('domains.show', [
            'domain' => $domain,
            'recipientCount' => $domain->recipients()->where('active', true)->count(),
            'quarantineCount' => $domain->quarantine()->where('verdict', 'quarantined')->count(),
        ]);
    }

    public function edit(MailDomain $domain)
    {
        $this->ensureAdmin();

        return view('domains.edit', [
            'domain' => $domain,
            'customers' => Customer::where('active', true)->orderBy('name')->get(),
            'policies' => SpamPolicy::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MailDomain $domain)
    {
        $this->ensureAdmin();
        $domain->update($this->validated($request, $domain));

        return redirect()->route('domains.show', $domain)
            ->with('status', "Domain \"{$domain->name}\" updated.");
    }

    public function destroy(MailDomain $domain)
    {
        $this->ensureAdmin();
        $name = $domain->name;
        $domain->delete();

        return redirect()->route('domains.index')->with('status', "Domain \"{$name}\" deleted.");
    }

    /**
     * Confirm control of the domain by looking for the TXT record. Ownership is
     * proved before we accept mail, otherwise anyone could add a domain they do
     * not own and read its quarantine.
     */
    public function verify(MailDomain $domain)
    {
        $this->authorizeCustomer($domain->customer_id);

        $expected = $domain->verificationRecord();
        $records = @dns_get_record($domain->name, DNS_TXT) ?: [];

        foreach ($records as $record) {
            if (trim($record['txt'] ?? '') === $expected) {
                $domain->update(['verified_at' => now()]);

                return back()->with('status', "Ownership of {$domain->name} verified.");
            }
        }

        return back()->with('status', "No matching TXT record found for {$domain->name} yet. DNS can take a few minutes to propagate.");
    }

    /**
     * Check the domain's MX records actually point at one of our nodes. Without
     * this a domain looks configured in the panel while its mail still flows
     * straight past the gateway.
     */
    public function checkMx(MailDomain $domain)
    {
        $this->authorizeCustomer($domain->customer_id);

        $records = @dns_get_record($domain->name, DNS_MX) ?: [];
        $hosts = array_map(fn ($r) => rtrim(strtolower($r['target'] ?? ''), '.'), $records);
        $nodes = \App\Models\Node::where('active', true)->pluck('hostname')
            ->map(fn ($h) => rtrim(strtolower($h), '.'))->all();

        $pointing = array_values(array_intersect($hosts, $nodes));
        $status = $pointing ? 'verified' : 'failed';
        $domain->update(['mx_status' => $status]);

        return back()->with('status', $pointing
            ? "MX for {$domain->name} points at: " . implode(', ', $pointing)
            : "MX for {$domain->name} does not point at any active node yet." .
              ($hosts ? ' Currently: ' . implode(', ', $hosts) : ' No MX records found.'));
    }

    private function validated(Request $request, ?MailDomain $domain = null): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'spam_policy_id' => ['nullable', 'exists:spam_policies,id'],
            'name' => [
                'required', 'string', 'max:191',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))+$/i',
                Rule::unique('mail_domains', 'name')->ignore($domain?->id),
            ],
            'destination_host' => ['nullable', 'string', 'max:191'],
            'destination_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'tls_policy' => ['required', Rule::in(['none', 'opportunistic', 'enforced'])],
            'recipient_mode' => ['required', Rule::in(['list', 'all'])],
            'active' => ['nullable', 'boolean'],
        ]);
    }

}
