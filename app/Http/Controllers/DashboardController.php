<?php

namespace App\Http\Controllers;

use App\Models\MemberPlan;
use App\Models\MembershipTier;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\TherapyAppointment;
use App\Models\TherapyRequest;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\TrainerClientAccessService;
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
        $user = $request->user()->load([
            'memberProfile.membershipTier',
            'activeMembershipSubscription.tier',
            'activeMembershipSubscription.payment',
        ]);

        return view('dashboards.member', [
            'user' => $user,
            'currentSubscription' => $user->activeMembershipSubscription,
            'tiers' => MembershipTier::query()->where('is_active', true)->orderBy('price')->get(),
        ]);
    }

    public function admin(): View
    {
        return view('dashboards.admin', [
            'userCount' => User::count(),
            'memberCount' => User::role('member')->count(),
            'trainerCount' => User::role('trainer')->count(),
            'therapistCount' => User::role('therapist')->count(),
            'pendingTrainerCount' => TrainerProfile::where('status', 'pending_review')->count(),
            'pendingTherapyRequests' => TherapyRequest::where('status', 'pending')->count(),
            'pendingTherapyAppointments' => TherapyAppointment::where('status', 'pending')->count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'pendingBookings' => TrainerBooking::where('status', 'pending')->count(),
            'serviceCount' => Service::count(),
            'productCount' => Product::count(),
        ]);
    }

    public function trainer(
        Request $request,
        TrainerClientAccessService $access,
    ): View {
        $profile = $request->user()->trainerProfile;
        $assignedMemberIds = $profile
            ? $access->assignedMembersQuery($profile)->pluck('users.id')
            : collect();
        $assignedClientCount = $assignedMemberIds->count();
        $reviewsThisMonth = $profile?->monthlyProgressReviews()
            ->whereIn('user_id', $assignedMemberIds)
            ->whereDate('review_month', today()->startOfMonth())
            ->count() ?? 0;
        return view('dashboards.trainer', [
            'profile' => $profile,
            'pendingBookings' => $profile?->bookings()->where('status', TrainerBooking::STATUS_PENDING)->count() ?? 0,
            'todaySessions' => $profile?->bookings()->with('member')->where('status', TrainerBooking::STATUS_ACCEPTED)->whereDate('confirmed_start_at', today())->orderBy('confirmed_start_at')->get() ?? collect(),
            'upcomingBookingCount' => $profile?->bookings()->upcoming()->count() ?? 0,
            'assignedClientCount' => $assignedClientCount,
            'activePlanCount' => $profile?->memberPlans()->where('status', MemberPlan::STATUS_ACTIVE)->count() ?? 0,
            'reviewsThisMonth' => $reviewsThisMonth,
        ]);
    }

    public function therapist(Request $request): View
    {
        $specialist = $request->user()->therapySpecialist;

        return view('dashboards.therapist', [
            'specialist' => $specialist,
            'pendingAppointments' => $specialist?->appointments()->where('status', TherapyAppointment::STATUS_PENDING)->count() ?? 0,
            'confirmedAppointments' => $specialist?->appointments()->where('status', TherapyAppointment::STATUS_CONFIRMED)->count() ?? 0,
            'todayAppointments' => $specialist?->appointments()->with(['user', 'treatment'])->where('status', TherapyAppointment::STATUS_CONFIRMED)->whereDate('confirmed_start_at', today())->orderBy('confirmed_start_at')->get() ?? collect(),
            'futureAppointments' => $specialist?->appointments()->upcoming()->count() ?? 0,
            'upcomingAppointments' => $specialist?->appointments()->with(['user', 'treatment'])->upcoming()->orderBy('confirmed_start_at')->limit(6)->get() ?? collect(),
            'completedAppointments' => $specialist?->appointments()->where('status', TherapyAppointment::STATUS_COMPLETED)->count() ?? 0,
            'cancelledAppointments' => $specialist?->appointments()->where('status', TherapyAppointment::STATUS_CANCELLED)->count() ?? 0,
        ]);
    }
}
