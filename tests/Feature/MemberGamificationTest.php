<?php

namespace Tests\Feature;

use App\Models\MonthlyProgressReview;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WellnessCompletion;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MemberGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-26 12:00:00');
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_xp_level_and_rank_are_derived_from_documented_existing_sources(): void
    {
        $member = $this->member();
        $workout = WorkoutPlan::firstOrFail();
        $wellness = WellnessActivity::firstOrFail();
        $trainers = TrainerProfile::approved()->take(2)->get();

        $this->workout($member, $workout, '2026-08-18', 80);
        $this->workout($member, $workout, '2026-08-19', 70);
        $this->wellness($member, $wellness, '2026-08-20', 40);
        $this->trainerSession($member, $trainers[0], '2026-08-21 10:00:00', TrainerBooking::STATUS_COMPLETED);
        $this->trainerSession($member, $trainers[0], '2026-08-22 10:00:00', TrainerBooking::STATUS_ACCEPTED);

        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainers[0]->id,
            'user_id' => $member->id,
            'review_month' => '2026-08-01',
            'goal_completion_percent' => 100,
        ]);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainers[1]->id,
            'user_id' => $member->id,
            'review_month' => '2026-08-01',
            'goal_completion_percent' => 100,
        ]);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainers[0]->id,
            'user_id' => $member->id,
            'review_month' => '2026-07-01',
            'goal_completion_percent' => 80,
        ]);

        $summary = app(GamificationService::class)->summaryFor($member);

        $this->assertSame(245, $summary['total_xp']);
        $this->assertSame(3, $summary['level']);
        $this->assertSame(45, $summary['xp_into_level']);
        $this->assertSame(55, $summary['xp_to_next_level']);
        $this->assertSame('Foundation', $summary['current_rank']['name']);
        $this->assertSame(0, $summary['current_streak']);
        $this->assertSame(4, $summary['longest_streak']);
        $this->assertSame(4, $summary['active_day_count']);

        $sources = collect($summary['sources'])->keyBy('key');
        $this->assertSame([2, 150], [$sources['workouts']['count'], $sources['workouts']['xp']]);
        $this->assertSame([1, 40], [$sources['wellness']['count'], $sources['wellness']['xp']]);
        $this->assertSame([1, 25], [$sources['trainer_sessions']['count'], $sources['trainer_sessions']['xp']]);
        $this->assertSame([1, 30], [$sources['monthly_goals']['count'], $sources['monthly_goals']['xp']]);
        $this->assertSame([0, 0], [$sources['streaks']['count'], $sources['streaks']['xp']]);
    }

    public function test_streaks_use_distinct_consecutive_activity_days_and_award_milestones(): void
    {
        $member = $this->member();
        $workout = WorkoutPlan::firstOrFail();
        $wellness = WellnessActivity::firstOrFail();

        foreach (range(20, 26) as $day) {
            $this->workout($member, $workout, "2026-08-{$day}", 10);
        }
        $this->wellness($member, $wellness, '2026-08-26', 5);
        $this->workout($member, $workout, '2026-08-01', 5);

        $summary = app(GamificationService::class)->summaryFor($member);

        $this->assertSame(8, $summary['active_day_count']);
        $this->assertSame(7, $summary['current_streak']);
        $this->assertSame(7, $summary['longest_streak']);
        $this->assertSame(100, $summary['total_xp']);
        $this->assertSame(2, $summary['level']);

        $streakSource = collect($summary['sources'])->firstWhere('key', 'streaks');
        $this->assertSame(1, $streakSource['count']);
        $this->assertSame(20, $streakSource['xp']);
    }

    public function test_member_progression_page_is_private_and_does_not_show_another_members_xp(): void
    {
        $member = $this->member('Progression Owner');
        $otherMember = $this->member('Other XP Member');
        $this->workout($otherMember, WorkoutPlan::firstOrFail(), '2026-08-26', 999);

        $this->get(route('member.progression.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($member)
            ->get(route('member.progression.index'))
            ->assertOk()
            ->assertSee('Level & XP', false)
            ->assertSee('0 total XP')
            ->assertSee('Level 1 · Initiate')
            ->assertSee('Your XP journey starts at zero.')
            ->assertSee('There is no hidden score')
            ->assertDontSee('999 total XP')
            ->assertDontSee('Other XP Member');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->get(route('member.progression.index'))
            ->assertForbidden();
    }

    public function test_member_progression_page_shows_the_current_level_and_xp_summary(): void
    {
        $member = $this->member();
        $this->workout($member, WorkoutPlan::firstOrFail(), '2026-08-26', 120);

        $this->actingAs($member)
            ->get(route('member.progression.index'))
            ->assertOk()
            ->assertSee('120 total XP')
            ->assertSee('Level 2 · Foundation')
            ->assertSee('80 XP')
            ->assertSee('Open quests & achievements', false);
    }

    private function member(string $name = 'Gamification Member'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');

        return $member;
    }

    private function workout(User $member, WorkoutPlan $workout, string $date, int $points): WorkoutCompletion
    {
        return WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => $workout->id,
            'completed_on' => $date,
            'points_awarded' => $points,
        ]);
    }

    private function wellness(User $member, WellnessActivity $activity, string $date, int $points): WellnessCompletion
    {
        return WellnessCompletion::create([
            'user_id' => $member->id,
            'wellness_activity_id' => $activity->id,
            'completed_on' => $date,
            'points_awarded' => $points,
        ]);
    }

    private function trainerSession(
        User $member,
        TrainerProfile $trainer,
        string $start,
        string $status,
    ): TrainerBooking {
        $confirmedStart = Carbon::parse($start);

        return TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => $confirmedStart,
            'confirmed_start_at' => $confirmedStart,
            'duration_minutes' => 60,
            'required_arrival_at' => $confirmedStart->copy()->subMinutes(15),
            'status' => $status,
        ]);
    }
}
