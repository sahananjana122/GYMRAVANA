<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $unreadNotificationCount = $user->unreadNotifications()->count();

        return view('layouts.app', [
            'navigationItems' => $this->navigationFor($user, $unreadNotificationCount),
            'unreadNotificationCount' => $unreadNotificationCount,
            'roleLabel' => $this->roleLabel($user),
        ]);
    }

    private function navigationFor(User $user, int $unreadNotificationCount): array
    {
        if ($user->hasRole('member')) {
            return [
                $this->item('Public Website', 'home'),
                $this->item('Dashboard', 'member.dashboard', ['member.dashboard']),
                $this->item('Trainers', 'trainers.index', ['trainers.*']),
                $this->item('Workouts', 'member.workouts.index', ['member.workouts.*']),
                $this->item('Meal Plan', 'member.meal-plan.index', ['member.meal-plan.*']),
                $this->item('Schedules', 'member.schedules.index', ['member.schedules.*']),
                $this->item('Progress', 'member.progress.index', ['member.progress.*', 'member.measurements.*']),
                $this->item('Level & XP', 'member.progression.index', ['member.progression.*']),
                $this->item('Quests', 'member.missions.index', ['member.missions.*']),
                $this->item('Master Gate', 'member.master-gate.index', ['member.master-gate.*']),
                $this->item('Mind & Wellness', 'member.wellness.index', ['member.wellness.*', 'member.therapy.*']),
                $this->item('Library', 'member.library.index', ['member.library.*']),
                $this->item('Alerts', 'notifications.index', ['notifications.*'], $unreadNotificationCount),
            ];
        }

        if ($user->hasRole('trainer')) {
            return [
                $this->item('Public Website', 'home'),
                $this->item('Dashboard', 'trainer.dashboard', ['trainer.dashboard']),
                $this->item('Clients & Plans', 'trainer.plans.index', ['trainer.plans.*'], null, 'Trainer work'),
                $this->item('Sessions', 'trainer.bookings.index', ['trainer.bookings.*'], null, 'Trainer work'),
                $this->item('Library', 'trainer.library.index', ['trainer.library.*'], null, 'Resources'),
                $this->item('Monthly Tracker', 'trainer.tracker.index', ['trainer.tracker.*'], null, 'Resources'),
                $this->item('Alerts', 'notifications.index', ['notifications.*'], $unreadNotificationCount, 'Account'),
                $this->item('Trainer Profile', 'trainer.profile.edit', ['trainer.profile.*'], null, 'Account'),
            ];
        }

        if ($user->hasRole('therapist')) {
            return [
                $this->item('Public Website', 'home'),
                $this->item('Dashboard', 'therapist.dashboard', ['therapist.dashboard']),
                $this->item('Appointments', 'therapist.appointments.index', ['therapist.appointments.*'], null, 'Therapy work'),
                $this->item('Alerts', 'notifications.index', ['notifications.*'], $unreadNotificationCount, 'Account'),
            ];
        }

        if ($user->hasRole('admin')) {
            return [
                $this->item('Public Website', 'home'),
                $this->item('Dashboard', 'admin.dashboard', ['admin.dashboard']),
                $this->item('Users', 'admin.users.index', ['admin.users.*'], null, 'People'),
                $this->item('Trainers', 'admin.trainers.index', ['admin.trainers.*'], null, 'People'),
                $this->item('Therapists', 'admin.therapists.index', ['admin.therapists.*'], null, 'People'),
                $this->item('Memberships', 'admin.memberships.index', ['admin.memberships.*', 'admin.members.*'], null, 'Services'),
                $this->item('Programmes', 'admin.services.index', ['admin.services.*'], null, 'Services'),
                $this->item('Trainer Bookings', 'admin.bookings.index', ['admin.bookings.*'], null, 'Schedules'),
                $this->item('Trainer Work', 'admin.trainer-work.index', ['admin.trainer-work.*'], null, 'Schedules'),
                $this->item('Therapy Leads', 'admin.therapy.index', ['admin.therapy.*'], null, 'Schedules'),
                $this->item('Therapy Appointments', 'admin.therapy-appointments.index', ['admin.therapy-appointments.*'], null, 'Schedules'),
                $this->item('Products', 'admin.products.index', ['admin.products.*', 'admin.product-categories.*'], null, 'Business'),
                $this->item('Orders', 'admin.orders.index', ['admin.orders.*'], null, 'Business'),
                $this->item('Finance & Reports', 'admin.finance.index', ['admin.finance.*'], null, 'Business'),
                $this->item('Notices', 'admin.notices.index', ['admin.notices.*'], null, 'Publishing'),
                $this->item('Events', 'admin.events.index', ['admin.events.*'], null, 'Publishing'),
                $this->item('Quests & Achievements', 'admin.gamification.index', ['admin.gamification.*'], null, 'Progression'),
                $this->item('Master Gate Reviews', 'admin.master-gate.index', ['admin.master-gate.*'], null, 'Progression'),
                $this->item('AI Data Readiness', 'admin.ai-readiness.index', ['admin.ai-readiness.*'], null, 'Progression'),
                $this->item('Notification Activity', 'admin.notifications.index', ['admin.notifications.*'], null, 'Publishing'),
                $this->item('My Alerts', 'notifications.index', ['notifications.*'], $unreadNotificationCount, 'Account'),
            ];
        }

        return [
            $this->item('Public Website', 'home'),
            $this->item('Account', 'profile.edit', ['profile.*']),
        ];
    }

    private function item(
        string $label,
        string $routeName,
        array $activePatterns = [],
        ?int $badge = null,
        ?string $group = null,
    ): array {
        return [
            'label' => $label,
            'href' => route($routeName),
            'active' => $activePatterns !== [] && request()->routeIs(...$activePatterns),
            'badge' => $badge,
            'group' => $group,
        ];
    }

    private function roleLabel(User $user): string
    {
        return match (true) {
            $user->hasRole('admin') => 'Administrator',
            $user->hasRole('trainer') => 'Personal Trainer',
            $user->hasRole('therapist') => 'Therapy Specialist',
            $user->hasRole('member') => 'Gym Member',
            default => 'Account',
        };
    }
}
