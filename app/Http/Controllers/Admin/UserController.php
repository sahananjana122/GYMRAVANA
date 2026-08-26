<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(20),
            'roles' => ['member', 'trainer', 'therapist', 'admin'],
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot change your own role.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['member', 'trainer', 'therapist', 'admin'])],
        ]);

        if ($validated['role'] === 'therapist' && ! $user->therapySpecialist()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Link this user to a therapy specialist from Therapist accounts before assigning the therapist role.',
            ]);
        }

        if ($user->hasRole('therapist') && $validated['role'] !== 'therapist') {
            $user->therapySpecialist()->update(['user_id' => null]);
        }

        $user->syncRoles([$validated['role']]);

        if ($validated['role'] === 'member') {
            $user->memberProfile()->firstOrCreate(
                ['user_id' => $user->id],
                ['joined_at' => today(), 'status' => 'active'],
            );
        }

        return back()->with('status', "{$user->name}'s role was updated.");
    }
}
