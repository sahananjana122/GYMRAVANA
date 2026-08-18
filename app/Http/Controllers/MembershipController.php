<?php

namespace App\Http\Controllers;

use App\Models\MembershipTier;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(): View
    {
        return view('memberships.index', ['tiers' => MembershipTier::where('is_active', true)->orderBy('price')->get()]);
    }
}
