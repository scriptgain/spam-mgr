<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToCustomer;
use App\Models\SpamPolicy;
use Illuminate\Http\Request;

class SpamPolicyController extends Controller
{
    use ScopesToCustomer;

    public function index()
    {
        $this->ensureAdmin();

        return view('policies.index', [
            'policies' => SpamPolicy::withCount('domains')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('policies.create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $policy = SpamPolicy::create($this->validated($request));

        return redirect()->route('policies.index')
            ->with('status', "Policy \"{$policy->name}\" created.");
    }

    public function edit(SpamPolicy $policy)
    {
        $this->ensureAdmin();

        return view('policies.edit', compact('policy'));
    }

    public function update(Request $request, SpamPolicy $policy)
    {
        $this->ensureAdmin();
        $policy->update($this->validated($request));

        return redirect()->route('policies.index')
            ->with('status', "Policy \"{$policy->name}\" updated.");
    }

    public function destroy(SpamPolicy $policy)
    {
        $this->ensureAdmin();

        // Domains fall back to the default policy, so deleting the default itself
        // would leave them with no thresholds at all.
        if ($policy->is_default) {
            return back()->with('status', 'The default policy cannot be deleted. Make another policy the default first.');
        }

        $name = $policy->name;
        $policy->delete();

        return redirect()->route('policies.index')->with('status', "Policy \"{$name}\" deleted. Its domains now use the default.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tag_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'tag2_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'kill_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'block_foreign_charset' => ['nullable', 'boolean'],
            'block_bulk' => ['nullable', 'boolean'],
            'subject_block_keywords' => ['nullable', 'string', 'max:10000'],
            'body_block_keywords' => ['nullable', 'string', 'max:10000'],
            'uri_allowlist' => ['nullable', 'string', 'max:10000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // Thresholds must climb. Out of order they produce silently wrong verdicts:
        // a kill below tag quarantines mail that was never even tagged.
        if ($data['tag2_level'] < $data['tag_level'] || $data['kill_level'] < $data['tag2_level']) {
            abort(422, 'Thresholds must increase: tag, then subject rewrite, then quarantine.');
        }

        return $data;
    }
}
