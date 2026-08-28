<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\GamificationMission;
use App\Models\MemberAchievement;
use App\Models\MemberMission;
use App\Models\User;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use App\Services\GamificationProgressService;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GamificationMissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-26 10:00:00');
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_quest_progress_starts_at_join_and_reward_is_awarded_exactly_once(): void
    {
        $member = $this->member();
        $plans = WorkoutPlan::query()->take(3)->get();
        $quest = GamificationMission::where('slug', 'workout-starter')->firstOrFail();

        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => $plans[0]->id,
            'completed_on' => today()->subDay(),
            'points_awarded' => 10,
        ]);

        Carbon::setTestNow('2026-08-26 11:00:00');
        $this->actingAs($member)
            ->post(route('member.missions.join', $quest))
            ->assertRedirect(route('member.missions.index'));

        $participation = MemberMission::firstOrFail();
        $this->assertSame(0, $participation->progress_value);
        $this->assertNull($participation->completed_at);

        foreach ($plans as $plan) {
            $this->post(route('member.workouts.complete', $plan))->assertRedirect();
        }

        $participation->refresh();
        $this->assertSame(3, $participation->progress_value);
        $this->assertNotNull($participation->completed_at);
        $this->assertSame(30, $participation->reward_xp_awarded);

        app(GamificationProgressService::class)->syncFor($member);
        $this->assertSame(1, MemberMission::whereNotNull('completed_at')->count());
        $this->assertSame(30, app(GamificationService::class)->summaryFor($member)['sources'][5]['xp']);
    }

    public function test_member_page_syncs_lifetime_achievements_without_awarding_extra_xp(): void
    {
        $member = $this->member();
        $plan = WorkoutPlan::firstOrFail();

        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => $plan->id,
            'completed_on' => today(),
            'points_awarded' => 25,
        ]);

        $this->actingAs($member)
            ->get(route('member.missions.index'))
            ->assertOk()
            ->assertSee('Quests & Achievements', false)
            ->assertSee('First Rep')
            ->assertSee('Unlocked');

        $this->assertDatabaseHas('member_achievements', [
            'user_id' => $member->id,
            'achievement_id' => Achievement::where('slug', 'first-rep')->value('id'),
        ]);
        $this->assertSame(25, app(GamificationService::class)->summaryFor($member)['total_xp']);

        $this->get(route('member.missions.index'))->assertOk();
        $this->assertSame(1, MemberAchievement::where('user_id', $member->id)->count());
    }

    public function test_closed_and_future_missions_cannot_be_joined(): void
    {
        $member = $this->member();
        $draft = $this->mission(['slug' => 'draft-quest', 'status' => GamificationMission::STATUS_DRAFT]);
        $future = $this->mission([
            'kind' => GamificationMission::KIND_CHALLENGE,
            'slug' => 'future-challenge',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
        ]);

        $this->actingAs($member)->post(route('member.missions.join', $draft))->assertSessionHasErrors('mission');
        $this->post(route('member.missions.join', $future))->assertSessionHasErrors('mission');
        $this->assertDatabaseCount('member_missions', 0);
    }

    public function test_mission_pages_are_role_protected(): void
    {
        $this->get(route('member.missions.index'))->assertRedirect(route('login'));

        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');
        $this->actingAs($trainer)->get(route('member.missions.index'))->assertForbidden();

        $member = $this->member();
        $this->actingAs($member)->get(route('admin.gamification.index'))->assertForbidden();
    }

    public function test_admin_can_create_definitions_but_cannot_rewrite_member_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification.index'))
            ->assertOk()
            ->assertSee('Quests & Achievements', false)
            ->assertSee('Create mission')
            ->assertSee('Create achievement');

        $this->post(route('admin.gamification.missions.store'), [
            'kind' => GamificationMission::KIND_CHALLENGE,
            'title' => 'Missing Dates',
            'description' => 'This challenge intentionally omits its required date window.',
            'metric' => GamificationMission::METRIC_ACTIVE_DAYS,
            'target_value' => 5,
            'reward_xp' => 20,
            'status' => GamificationMission::STATUS_DRAFT,
        ])->assertSessionHasErrors(['starts_on', 'ends_on']);

        $payload = [
            'kind' => GamificationMission::KIND_QUEST,
            'title' => 'Admin Test Quest',
            'description' => 'A transparent quest created by an administrator.',
            'metric' => GamificationMission::METRIC_WELLNESS,
            'target_value' => 2,
            'reward_xp' => 15,
            'starts_on' => null,
            'ends_on' => null,
            'status' => GamificationMission::STATUS_PUBLISHED,
        ];
        $this->post(route('admin.gamification.missions.store'), $payload)
            ->assertRedirect(route('admin.gamification.index'));

        $mission = GamificationMission::where('slug', 'admin-test-quest')->firstOrFail();
        $member = $this->member();
        MemberMission::create([
            'gamification_mission_id' => $mission->id,
            'user_id' => $member->id,
            'joined_at' => now(),
        ]);

        $this->patch(
            route('admin.gamification.missions.update', $mission),
            array_merge($payload, ['reward_xp' => 999]),
        )->assertSessionHasErrors('mission');
        $this->assertSame(15, $mission->fresh()->reward_xp);

        $this->delete(route('admin.gamification.missions.destroy', $mission))
            ->assertSessionHasErrors('mission');
        $this->assertDatabaseHas('gamification_missions', ['id' => $mission->id]);
    }

    public function test_unlocked_achievement_rule_and_record_cannot_be_deleted_or_redefined(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = $this->member();
        $achievement = Achievement::where('slug', 'first-rep')->firstOrFail();
        MemberAchievement::create([
            'achievement_id' => $achievement->id,
            'user_id' => $member->id,
            'progress_value' => 1,
            'unlocked_at' => now(),
        ]);
        $payload = [
            'title' => $achievement->title,
            'description' => $achievement->description,
            'metric' => $achievement->metric,
            'threshold' => 9,
            'sort_order' => $achievement->sort_order,
            'is_active' => '1',
        ];

        $this->actingAs($admin)
            ->patch(route('admin.gamification.achievements.update', $achievement), $payload)
            ->assertSessionHasErrors('achievement');
        $this->assertSame(1, $achievement->fresh()->threshold);

        $this->delete(route('admin.gamification.achievements.destroy', $achievement))
            ->assertSessionHasErrors('achievement');
        $this->assertDatabaseHas('member_achievements', ['achievement_id' => $achievement->id]);
    }

    private function member(): User
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        return $member;
    }

    private function mission(array $overrides): GamificationMission
    {
        return GamificationMission::create(array_merge([
            'kind' => GamificationMission::KIND_QUEST,
            'title' => 'Test Mission',
            'slug' => 'test-mission',
            'description' => 'A mission used to verify availability rules.',
            'metric' => GamificationMission::METRIC_WORKOUTS,
            'target_value' => 1,
            'reward_xp' => 10,
            'status' => GamificationMission::STATUS_PUBLISHED,
        ], $overrides));
    }
}
