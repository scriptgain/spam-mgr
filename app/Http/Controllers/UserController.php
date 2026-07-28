<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admins only.');
    }

    public function index()
    {
        $this->ensureAdmin();
        $users = User::with('customer')->orderBy('name')->get();

        return view('settings.users.index', compact('users'));
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('settings.users.create', ['customers' => Customer::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'user', 'customer'])],
            'customer_id' => ['nullable', 'required_if:role,customer', 'exists:customers,id'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['password_changed_at'] = now();
        if ($data['role'] !== 'customer') {
            $data['customer_id'] = null;
        }
        User::create($data);

        return redirect()->route('settings.users.index')->with('status', "User \"{$data['name']}\" created.");
    }

    public function edit(User $user)
    {
        $this->ensureAdmin();

        return view('settings.users.edit', [
            'user' => $user,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'user', 'customer'])],
            'customer_id' => ['nullable', 'required_if:role,customer', 'exists:customers,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        // Don't let an admin demote themselves and lose access.
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->with('status', 'You cannot remove your own admin role.');
        }
        if ($data['role'] !== 'customer') {
            $data['customer_id'] = null;
        }
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
            $data['password_changed_at'] = now();
        }
        $user->update($data);

        return redirect()->route('settings.users.index')->with('status', "User \"{$user->name}\" updated.");
    }

    public function destroy(User $user)
    {
        $this->ensureAdmin();
        if ($user->id === auth()->id()) {
            return back()->with('status', 'You cannot delete your own account.');
        }
        $name = $user->name;
        $user->delete();

        return redirect()->route('settings.users.index')->with('status', "User \"{$name}\" deleted.");
    }
}
