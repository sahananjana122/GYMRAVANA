<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MembershipTierController extends Controller
{
    public function index(): View
    {
        return view('admin.memberships.index', [
            'tiers' => MembershipTier::withCount('memberProfiles')->orderBy('price')->get(),
            'members' => User::role('member')->with('memberProfile.membershipTier')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        MembershipTier::create($validated + ['slug' => Str::slug($validated['name'])]);

        return back()->with('status', 'Membership tier created.');
    }

    public function update(Request $request, MembershipTier $membershipTier): RedirectResponse
    {
        $membershipTier->update($this->validated($request));

        return back()->with('status', 'Membership tier updated.');
    }

    public function assign(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('member'), 422);
        $validated = $request->validate(['membership_tier_id' => ['required', 'exists:membership_tiers,id']]);
        MemberProfile::updateOrCreate(['user_id' => $user->id], $validated + ['status' => 'active']);

        return back()->with('status', 'Member tier reassigned.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'string', 'max:30'],
            'features_text' => ['required', 'string', 'max:2000'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'price' => $validated['price'],
            'billing_period' => $validated['billing_period'],
            'features' => collect(preg_split('/\r\n|\r|\n/', $validated['features_text']))->map(fn ($item) => trim($item))->filter()->values()->all(),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
