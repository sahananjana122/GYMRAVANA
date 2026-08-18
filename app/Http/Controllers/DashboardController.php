<?php

namespace App\Http\Controllers;

use App\Models\MembershipTier;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\TherapyAppointment;
use App\Models\TherapyRequest;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $route = $request->user()->dashboardRouteName();
        abort_if($route === 'dashboard', 403, 'Your account does not have an assigned role.');

        return redirect()->route($route);
    }

    public function member(Request $request): View
    {
        $user = $request->user()->load(['memberProfile.membershipTier', 'enrolledServices.category']);

        return view('dashboards.member', [
            'user' => $user,
            'totalPoints' => $user->totalPoints(),
            'workoutCount' => $user->workoutCompletions()->count(),
            'wellnessCount' => $user->wellnessCompletions()->count(),
            'latestMeasurement' => $user->bodyMeasurements()->latest('recorded_on')->first(),
            'pendingTherapyRequests' => $user->therapyRequests()->whereIn('status', ['pending', 'reviewed'])->count(),
            'availableWorkouts' => WorkoutPlan::where('is_active', true)->count(),
            'orders' => $user->orders()->with('items')->latest()->limit(5)->get(),
            'bookings' => $user->trainerBookings()->with('trainerProfile.user')->latest()->limit(5)->get(),
            'tiers' => MembershipTier::where('is_active', true)->orderBy('price')->get(),
        ]);
    }

    public function admin(): View
    {
        return view('dashboards.admin', [
            'userCount' => User::count(),
            'memberCount' => User::role('member')->count(),
            'trainerCount' => User::role('trainer')->count(),
            'pendingTrainerCount' => TrainerProfile::where('status', 'pending_review')->count(),
            'pendingTherapyRequests' => TherapyRequest::where('status', 'pending')->count(),
            'pendingTherapyAppointments' => TherapyAppointment::where('status', 'pending')->count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'pendingBookings' => TrainerBooking::where('status', 'pending')->count(),
            'serviceCount' => Service::count(),
            'productCount' => Product::count(),
        ]);
    }

    public function trainer(Request $request): View
    {
        $profile = $request->user()->trainerProfile;

        return view('dashboards.trainer', [
            'profile' => $profile,
            'pendingBookings' => $profile?->bookings()->where('status', 'pending')->count() ?? 0,
            'upcomingBookings' => $profile?->bookings()->with('member')->where('requested_datetime', '>=', now())->orderBy('requested_datetime')->limit(6)->get() ?? collect(),
        ]);
    }
}
