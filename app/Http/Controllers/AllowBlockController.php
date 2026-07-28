<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\AllowBlockEntry;
use App\Models\MailDomain;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AllowBlockController extends Controller
{
    use ScopesToCustomer;

    public function index(Request $request)
    {
        $query = AllowBlockEntry::with(['domain', 'recipient', 'customer']);

        // Portal users see rules scoped to them: their own customer-wide rules and
        // anything on their domains. Operator-wide rules stay hidden, since they
        // are not theirs to see or remove.
        if (($customerId = $this->viewerCustomerId()) !== null) {
            $query->where(function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                    ->orWhereHas('domain', fn ($d) => $d->where('customer_id', $customerId));
            });
        }

        if ($list = $request->get('list')) {
            $query->where('list', $list);
        }
        if ($search = trim((string) $request->get('q'))) {
            $query->where('value', 'like', "%{$search}%");
        }

        $domainQuery = MailDomain::query()->orderBy('name');
        $this->scopeToViewer($domainQuery);

        return view('rules.index', [
            'entries' => $query->latest()->paginate(100)->withQueryString(),
            'domains' => $domainQuery->get(['id', 'name']),
            'list' => $list,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mail_domain_id' => ['nullable', 'exists:mail_domains,id'],
            'type' => ['required', Rule::in(AllowBlockEntry::TYPES)],
            'value' => ['required', 'string', 'max:191'],
            'list' => ['required', Rule::in(AllowBlockEntry::LISTS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertValueMatchesType($data['type'], $data['value']);

        if (! empty($data['mail_domain_id'])) {
            $domain = MailDomain::findOrFail($data['mail_domain_id']);
            $this->authorizeCustomer($domain->customer_id);
            $data['customer_id'] = $domain->customer_id;
        } else {
            // A portal user with no domain selected gets a customer-wide rule, never
            // an operator-wide one.
            $customerId = $this->viewerCustomerId();
            if ($customerId === null) {
                $this->ensureAdmin();
            }
            $data['customer_id'] = $customerId;
        }

        AllowBlockEntry::create($data);

        return back()->with('status', "Rule for \"{$data['value']}\" added.");
    }

    public function destroy(AllowBlockEntry $entry)
    {
        $this->authorizeEntry($entry);
        $value = $entry->value;
        $entry->delete();

        return back()->with('status', "Rule for \"{$value}\" removed.");
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $entries = AllowBlockEntry::whereIn('id', $data['ids'])->get()
            ->filter(function (AllowBlockEntry $e) {
                $viewer = $this->viewerCustomerId();

                return $viewer === null || $e->customer_id === $viewer;
            });

        $count = $entries->count();
        AllowBlockEntry::whereIn('id', $entries->pluck('id'))->delete();

        return back()->with('status', "{$count} rule(s) removed.");
    }

    private function authorizeEntry(AllowBlockEntry $entry): void
    {
        $viewer = $this->viewerCustomerId();
        if ($viewer === null) {
            return;
        }
        if ($entry->customer_id !== $viewer) {
            abort(403);
        }
    }

    /**
     * A malformed value is silently dead weight: it never matches, so the operator
     * believes a sender is blocked when it is not.
     */
    private function assertValueMatchesType(string $type, string $value): void
    {
        $ok = match ($type) {
            'sender' => (bool) filter_var($value, FILTER_VALIDATE_EMAIL),
            'domain' => (bool) preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))+$/i', $value),
            'ip' => (bool) filter_var($value, FILTER_VALIDATE_IP)
                || (bool) preg_match('#^\d{1,3}(\.\d{1,3}){3}/\d{1,2}$#', $value),
            default => false,
        };

        abort_unless($ok, 422, "\"{$value}\" is not a valid {$type}.");
    }
}
