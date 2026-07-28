<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admins only.');
    }

    public function index()
    {
        $this->ensureAdmin();

        $customers = Customer::withCount(['domains', 'recipients', 'users'])
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('customers.create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $data = $this->validated($request);
        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)
            ->with('status', "Customer \"{$customer->name}\" created.");
    }

    public function show(Customer $customer)
    {
        $this->ensureAdmin();

        $customer->load(['domains.policy', 'users']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->ensureAdmin();

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->ensureAdmin();
        $customer->update($this->validated($request));

        return redirect()->route('customers.show', $customer)
            ->with('status', "Customer \"{$customer->name}\" updated.");
    }

    public function destroy(Customer $customer)
    {
        $this->ensureAdmin();
        $name = $customer->name;
        // Domains, mailboxes, quarantine and rules cascade. Their portal users are
        // left behind with a null customer_id rather than being deleted outright,
        // so removing a customer never silently destroys logins.
        $customer->delete();

        return redirect()->route('customers.index')->with('status', "Customer \"{$name}\" deleted.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'active' => ['nullable', 'boolean'],
        ]);
    }
}
