<?php

namespace Tests\Feature;

use App\Models\MemberPlan;
use App\Models\MemberProfile;
use App\Models\TherapyAppointment;
use App\Models\TherapySpecialist;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WellnessCompletion;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberDashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_member_dashboard_is_a_focused_private_progress_photo_gallery(): void
    {
        $member = $this->member();

        $this->actingAs($member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('My Transformation')
            ->assertSee('Before & after photos', false)
            ->assertSee('dashboard-watermark', false)
            ->assertDontSee('Schedule & Plans', false)
            ->assertDontSee('Book Sessions')
            ->assertDontSee('Library & Movies', false);
    }

    public function test_member_sees_only_their_own_upcoming_sessions_and_non_draft_plans(): void
    {
        $member = $this->member('Dashboard Member');
        $otherMember = $this->member('Private Other Member');
        $trainer = TrainerProfile::approved()->with('user')->firstOrFail();
        $start = now()->addDays(3)->startOfHour();
        $this->trainerSession($member, $trainer, $start, 'Visible trainer update');
        $this->trainerSession($otherMember, $trainer, $start->copy()->addDay(), 'Private trainer update');

        $specialist = TherapySpecialist::firstOrFail();
        $this->therapySession($member, $specialist, $start->copy()->addHours(3));
        $otherSpecialist = TherapySpecialist::whereKeyNot($specialist->id)->firstOrFail();
        $this->therapySession($otherMember, $otherSpecialist, $start->copy()->addDays(2));

        $workoutPlan = $this->plan($member, $trainer, MemberPlan::TYPE_WORKOUT, 'My Strength Foundation');
        $workoutPlan->items()->create([
            'day_of_week' => 1,
            'scheduled_time' => '07:30:00',
            'section' => 'Strength',
            'title' => 'Goblet squat practice',
            'instructions' => 'Use a comfortable load and controlled repetitions.',
            'target' => '3 × 8',
        ]);
        $this->plan($member, $trainer, MemberPlan::TYPE_MEAL, 'My Balanced Meal Guide');
        $this->plan($member, $trainer, MemberPlan::TYPE_WORKOUT, 'Secret Draft Plan', MemberPlan::STATUS_DRAFT);
        $this->plan($otherMember, $trainer, MemberPlan::TYPE_WORKOUT, 'Other Member Private Plan');

        $this->actingAs($member)
            ->get(route('member.schedules.index'))
            ->assertOk()
            ->assertSee($trainer->user->name)
            ->assertSee('Visible trainer update')
            ->assertDontSee('Private trainer update')
            ->assertSee($specialist->name)
            ->assertDontSee($otherSpecialist->name);

        $this->get(route('member.workouts.index'))
            ->assertOk()
            ->assertSee('My Strength Foundation')
            ->assertSee('Goblet squat practice')
            ->assertSee('Monday · 07:30 · Strength')
            ->assertDontSee('Secret Draft Plan')
            ->assertDontSee('Other Member Private Plan');

        $this->get(route('member.meal-plan.index'))
            ->assertOk()
            ->assertSee('My Balanced Meal Guide')
            ->assertDontSee('Secret Draft Plan')
            ->assertDontSee('Other Member Private Plan');
    }

    public function test_monthly_progress_summary_uses_only_the_signed_in_members_current_month_data(): void
    {
        $member = $this->member();
        $otherMember = $this->member('Other Progress Member');
        $workout = WorkoutPlan::firstOrFail();
        $wellness = WellnessActivity::firstOrFail();

        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => $workout->id,
            'completed_on' => now()->startOfMonth()->addDay(),
            'points_awarded' => 20,
        ]);
        WellnessCompletion::create([
            'user_id' => $member->id,
            'wellness_activity_id' => $wellness->id,
            'completed_on' => now()->startOfMonth()->addDays(2),
            'points_awarded' => 10,
        ]);
        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => $workout->id,
            'completed_on' => now()->subMonth()->startOfMonth(),
            'points_awarded' => 999,
        ]);
        WorkoutCompletion::create([
            'user_id' => $otherMember->id,
            'workout_plan_id' => $workout->id,
            'completed_on' => now()->startOfMonth()->addDays(3),
            'points_awarded' => 500,
        ]);
        $member->bodyMeasurements()->create([
            'recorded_on' => now()->startOfMonth()->addDay(),
            'weight_kg' => 75,
        ]);
        $member->bodyMeasurements()->create([
            'recorded_on' => now()->startOfMonth()->addDays(10),
            'weight_kg' => 74.5,
        ]);

        $this->actingAs($member)
            ->get(route('member.progress.index'))
            ->assertOk()
            ->assertSeeInOrder(['30', 'Workout &amp; mind XP'], false)
            ->assertSee('2 active days')
            ->assertSee('-0.50 kg');
    }

    public function test_configured_library_url_is_rendered_as_a_safe_external_link(): void
    {
        $member = $this->member();
        config()->set('gymravana.library.url', 'https://drive.google.com/drive/folders/example');
        config()->set('gymravana.library.label', 'Member Learning Collection');

        $this->actingAs($member)
            ->get(route('member.library.index'))
            ->assertOk()
            ->assertSee('Member Learning Collection')
            ->assertSee('https://drive.google.com/drive/folders/example', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('Google Drive permissions still apply');
    }

    public function test_unsafe_library_scheme_is_not_rendered_as_a_link(): void
    {
        $member = $this->member();
        config()->set('gymravana.library.url', 'javascript:alert(1)');

        $this->actingAs($member)
            ->get(route('member.library.index'))
            ->assertOk()
            ->assertSee('Library link not configured.')
            ->assertDontSee('javascript:alert(1)', false);
    }

    private function member(string $name = 'Dashboard Test Member'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');
        MemberProfile::create(['user_id' => $member->id, 'status' => 'active']);

        return $member;
    }

    private function trainerSession(User $member, TrainerProfile $trainer, mixed $start, string $message): TrainerBooking
    {
        return TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => $start,
            'confirmed_start_at' => $start,
            'duration_minutes' => 60,
            'required_arrival_at' => $start->copy()->subMinutes(15),
            'status' => TrainerBooking::STATUS_ACCEPTED,
            'trainer_message' => $message,
        ]);
    }

    private function therapySession(User $member, TherapySpecialist $specialist, mixed $start): TherapyAppointment
    {
        $treatment = $specialist->treatments()->firstOrFail();

        return TherapyAppointment::create([
            'appointment_number' => (string) Str::uuid(),
            'user_id' => $member->id,
            'therapy_condition_id' => $treatment->conditions()->first()?->id,
            'treatment_id' => $treatment->id,
            'therapy_specialist_id' => $specialist->id,
            'customer_name' => $member->name,
            'contact_email' => $member->email,
            'preferred_datetime' => $start,
            'confirmed_start_at' => $start,
            'duration_minutes' => 60,
            'required_arrival_at' => $start->copy()->subMinutes(15),
            'status' => TherapyAppointment::STATUS_CONFIRMED,
        ]);
    }

    private function plan(
        User $member,
        TrainerProfile $trainer,
        string $type,
        string $title,
        string $status = MemberPlan::STATUS_ACTIVE,
    ): MemberPlan {
        return MemberPlan::create([
            'user_id' => $member->id,
            'trainer_profile_id' => $trainer->id,
            'created_by' => $trainer->user_id,
            'type' => $type,
            'title' => $title,
            'overview' => 'A structured plan assigned by the trainer.',
            'start_date' => today()->subDay(),
            'end_date' => today()->addMonth(),
            'status' => $status,
            'version' => 1,
            'assigned_at' => now()->subHour(),
        ]);
    }
}
