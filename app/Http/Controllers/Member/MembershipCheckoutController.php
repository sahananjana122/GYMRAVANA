<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MembershipSubscription;
use App\Models\MembershipTier;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipCheckoutController extends Controller
{
    public function show(Request $request, MembershipSubscription $membershipSubscription): View
    {
        abort_unless($membershipSubscription->user_id === $request->user()->id, 403);

        return view('member.membership.checkout', [
            'subscription' => $membershipSubscription->load(['tier', 'payment']),
        ]);
    }

    public function complete(
        Request $request,
        MembershipSubscription $membershipSubscription,
        MembershipService $memberships,
    ): RedirectResponse {
        abort_unless($membershipSubscription->user_id === $request->user()->id, 403);

        $memberships->completeDevelopmentPayment($request->user(), $membershipSubscription);

        return redirect()->route('member.dashboard')
            ->with('status', 'Membership activated successfully. Your permanent member number is ready.');
    }

    public function renew(Request $request, MembershipService $memberships): RedirectResponse
    {
        $validated = $request->validate([
            'membership_tier_id' => ['required', 'integer', 'exists:membership_tiers,id'],
        ]);
        $tier = MembershipTier::query()
            ->whereKey($validated['membership_tier_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $subscription = $memberships->createPendingSubscription($request->user(), $tier, false);

        return redirect()->route('member.membership.checkout', $subscription);
    }
}
