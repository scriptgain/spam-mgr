<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\MailDomain;
use App\Models\MailRecipient;
use Illuminate\Http\Request;

class MailRecipientController extends Controller
{
    use ScopesToCustomer;

    public function index(Request $request)
    {
        $query = MailRecipient::with('domain');
        $this->scopeToViewer($query);

        if ($domainId = $request->get('domain')) {
            $query->where('mail_domain_id', $domainId);
        }
        if ($search = trim((string) $request->get('q'))) {
            $query->where('address', 'like', "%{$search}%");
        }

        $domainQuery = MailDomain::query()->orderBy('name');
        $this->scopeToViewer($domainQuery);

        return view('mailboxes.index', [
            'recipients' => $query->orderBy('address')->paginate(100)->withQueryString(),
            'domains' => $domainQuery->get(['id', 'name']),
            'search' => $search,
            'domainId' => $domainId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mail_domain_id' => ['required', 'exists:mail_domains,id'],
            'address' => ['required', 'email', 'max:191'],
            'filtering_enabled' => ['nullable', 'boolean'],
        ]);

        $domain = MailDomain::findOrFail($data['mail_domain_id']);
        $this->authorizeCustomer($domain->customer_id);
        $this->assertAddressMatchesDomain($data['address'], $domain);

        if ($domain->recipients()->where('address', strtolower(trim($data['address'])))->exists()) {
            return back()->with('status', 'That mailbox already exists on this domain.');
        }

        MailRecipient::create($data + ['customer_id' => $domain->customer_id]);

        return back()->with('status', "Mailbox \"{$data['address']}\" added.");
    }

    /**
     * Paste-a-list import. MSPs onboard a domain with dozens of mailboxes at once
     * and adding them one at a time is the difference between using the product
     * and not.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'mail_domain_id' => ['required', 'exists:mail_domains,id'],
            'addresses' => ['required', 'string', 'max:100000'],
        ]);

        $domain = MailDomain::findOrFail($data['mail_domain_id']);
        $this->authorizeCustomer($domain->customer_id);

        $existing = $domain->recipients()->pluck('address')->all();
        $added = 0;
        $skipped = 0;

        foreach (preg_split('/[\r\n,;]+/', $data['addresses']) as $line) {
            $address = strtolower(trim($line));
            if ($address === '') {
                continue;
            }
            if (! filter_var($address, FILTER_VALIDATE_EMAIL)
                || ! str_ends_with($address, '@' . $domain->name)
                || in_array($address, $existing, true)) {
                $skipped++;

                continue;
            }

            MailRecipient::create([
                'mail_domain_id' => $domain->id,
                'customer_id' => $domain->customer_id,
                'address' => $address,
            ]);
            $existing[] = $address;
            $added++;
        }

        return back()->with('status', "{$added} mailbox(es) added, {$skipped} skipped (invalid, wrong domain, or already present).");
    }

    public function toggleFiltering(MailRecipient $recipient)
    {
        $this->authorizeCustomer($recipient->customer_id);
        $recipient->update(['filtering_enabled' => ! $recipient->filtering_enabled]);

        return back()->with('status', $recipient->filtering_enabled
            ? "Spam filtering enabled for {$recipient->address}."
            : "Spam filtering bypassed for {$recipient->address}. Virus scanning stays on.");
    }

    public function destroy(MailRecipient $recipient)
    {
        $this->authorizeCustomer($recipient->customer_id);
        $address = $recipient->address;
        $recipient->delete();

        return back()->with('status', "Mailbox \"{$address}\" removed.");
    }

    /**
     * A mailbox on the wrong domain would never receive filtered mail and its
     * presence in the recipient list would silently widen what the node accepts.
     */
    private function assertAddressMatchesDomain(string $address, MailDomain $domain): void
    {
        if (! str_ends_with(strtolower(trim($address)), '@' . $domain->name)) {
            abort(422, "Address must end with @{$domain->name}.");
        }
    }
}
