<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(20),
            'roles' => ['member', 'trainer', 'admin'],
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot change your own role.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['member', 'trainer', 'admin'])],
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('status', "{$user->name}'s role was updated.");
    }
}
