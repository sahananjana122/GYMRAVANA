<?php

namespace App\Http\Controllers;

use App\Models\MemberPlan;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\TherapyAppointment;
use App\Models\TherapyRequest;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\ExternalLibraryService;
use App\Services\MemberDashboardService;
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

    public function member(Request $request, MemberDashboardService $dashboard): View
    {
        return view('dashboards.member', $dashboard->dataFor($request->user()));
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
        ExternalLibraryService $library,
    ): View {
        $profile = $request->user()->trainerProfile;
        $assignedClients = $profile
            ? $access->assignedMembersQuery($profile)->with('memberProfile.membershipTier')->orderBy('name')->limit(6)->get()
            : collect();

        return view('dashboards.trainer', [
            'profile' => $profile,
            'pendingBookings' => $profile?->bookings()->where('status', TrainerBooking::STATUS_PENDING)->count() ?? 0,
            'todaySessions' => $profile?->bookings()->with('member')->where('status', TrainerBooking::STATUS_ACCEPTED)->whereDate('confirmed_start_at', today())->orderBy('confirmed_start_at')->get() ?? collect(),
            'upcomingBookings' => $profile?->bookings()->with('member')->upcoming()->orderBy('confirmed_start_at')->limit(6)->get() ?? collect(),
            'completedBookings' => $profile?->bookings()->where('status', TrainerBooking::STATUS_COMPLETED)->count() ?? 0,
            'cancelledBookings' => $profile?->bookings()->whereIn('status', [TrainerBooking::STATUS_CANCELLED, TrainerBooking::STATUS_DECLINED])->count() ?? 0,
            'assignedClientCount' => $profile ? $access->assignedMembersQuery($profile)->count() : 0,
            'assignedClients' => $assignedClients,
            'activePlanCount' => $profile?->memberPlans()->where('status', MemberPlan::STATUS_ACTIVE)->count() ?? 0,
            'reviewsThisMonth' => $profile?->monthlyProgressReviews()->whereDate('review_month', today()->startOfMonth())->count() ?? 0,
            'library' => $library->details(),
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
