<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $members = User::role('member')
            ->with(['memberProfile.membershipTier', 'activeMembershipSubscription.tier'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('memberProfile', fn ($profile) => $profile->where('membership_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.members.index', compact('members', 'search'));
    }

    public function show(User $user): View
    {
        abort_unless($user->hasRole('member'), 404);
        $user->load([
            'memberProfile.membershipTier',
            'membershipSubscriptions' => fn ($query) => $query->with(['tier', 'payment'])->latest('id'),
        ]);

        return view('admin.members.show', ['member' => $user]);
    }
}
