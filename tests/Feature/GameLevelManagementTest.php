<?php

namespace Tests\Feature;

use App\Models\GameGoal;
use App\Models\GameLevel;
use App\Models\User;
use App\Services\GameLevelProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameLevelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_initial_six_level_path_is_seeded_with_the_required_targets(): void
    {
        $this->assertSame(6, GameLevel::where('is_active', true)->count());
        $this->assertDatabaseHas('game_goals', ['slug' => 'level-1-veerasana-duration', 'metric_type' => GameGoal::METRIC_DURATION, 'target_value' => 30]);
        $this->assertDatabaseHas('game_goals', ['slug' => 'level-4-mayura-form', 'metric_type' => GameGoal::METRIC_PERCENTAGE, 'target_value' => 80]);
        $this->assertDatabaseHas('game_goals', ['slug' => 'level-6-push-ups', 'metric_type' => GameGoal::METRIC_REPETITIONS, 'target_value' => 20]);
        $this->assertTrue(GameLevel::where('number', 6)->firstOrFail()->unlocks_master_gate);
    }

    public function test_admin_can_change_a_goal_and_members_see_the_live_requirement(): void
    {
        $admin = $this->userWithRole('admin');
        $member = $this->userWithRole('member');
        $goal = GameGoal::where('slug', 'level-1-chakrasana-form')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.game-goals.update', $goal), $this->goalPayload($goal, ['target_value' => 65]))
            ->assertSessionHasNoErrors();

        $this->assertSame('65.00', $goal->fresh()->target_value);
        $this->actingAs($member)->get(route('member.progression.index'))
            ->assertOk()
            ->assertSee('Chakrasana')
            ->assertSee('65% form completion');
    }

    public function test_admin_level_builder_shows_the_seeded_path_and_goal_controls(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Game level builder')
            ->assertSee(route('admin.game-levels.index'));

        $this->actingAs($admin)
            ->get(route('admin.game-levels.index'))
            ->assertOk()
            ->assertSee('Level Builder')
            ->assertSee('Add a goal')
            ->assertSee('30 minutes')
            ->assertSee('Game Levels');
    }

    public function test_percentage_goals_cannot_be_configured_above_one_hundred_percent(): void
    {
        $admin = $this->userWithRole('admin');
        $goal = GameGoal::where('slug', 'level-1-chakrasana-form')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.game-goals.update', $goal), $this->goalPayload($goal, ['target_value' => 101]))
            ->assertSessionHasErrors('target_value');

        $this->assertSame('50.00', $goal->fresh()->target_value);
    }

    public function test_admin_can_add_and_remove_an_unused_level_and_goal(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.game-levels.store'), [
            'number' => 7,
            'name' => 'Level 7',
            'description' => 'Advanced configurable path.',
            'is_active' => 1,
            'unlocks_master_gate' => 0,
        ])->assertSessionHasNoErrors();

        $level = GameLevel::where('number', 7)->firstOrFail();
        $this->post(route('admin.game-levels.goals.store', $level), [
            'exercise_name' => 'Plank Hold',
            'metric_type' => GameGoal::METRIC_DURATION,
            'target_value' => 8,
            'validation_method' => GameGoal::VALIDATION_TRAINER,
            'sort_order' => 10,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $goal = $level->goals()->firstOrFail();
        $this->assertSame('8 minutes', $goal->requirementLabel());
        $this->delete(route('admin.game-goals.destroy', $goal))->assertSessionHasNoErrors();
        $this->delete(route('admin.game-levels.destroy', $level))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('game_levels', ['id' => $level->id]);
    }

    public function test_level_unlocks_are_recalculated_when_an_admin_raises_a_target(): void
    {
        $member = $this->userWithRole('member');
        $service = app(GameLevelProgressionService::class);
        $levelOne = GameLevel::where('number', 1)->with('goals')->firstOrFail();

        foreach ($levelOne->goals as $goal) {
            $service->record($member, $goal, (float) $goal->target_value, null, 'trainer');
        }

        $this->assertSame(2, $service->summaryFor($member)['current']['level']->number);

        $duration = $levelOne->goals->firstWhere('slug', 'level-1-veerasana-duration');
        $duration->update(['target_value' => 45]);

        $recalculated = $service->summaryFor($member);
        $this->assertSame(1, $recalculated['current']['level']->number);
        $this->assertFalse($recalculated['levels']->first()['completed']);
    }

    public function test_completing_every_configured_goal_unlocks_the_master_gate_path(): void
    {
        $member = $this->userWithRole('member');
        $service = app(GameLevelProgressionService::class);
        $running = GameGoal::where('slug', 'level-6-running')->firstOrFail();
        $running->update([
            'pace_target' => 8,
            'pace_unit' => GameGoal::PACE_KMH,
        ]);

        foreach (GameGoal::active()->get() as $goal) {
            $service->record($member, $goal, (float) $goal->target_value, $goal->pace_target !== null ? (float) $goal->pace_target : null, 'trainer');
        }

        $summary = $service->summaryFor($member);
        $this->assertTrue($summary['master_gate_unlocked']);
        $this->assertSame(6, $summary['highest_completed_level']);
    }

    public function test_running_goal_cannot_complete_until_an_admin_configures_a_pace(): void
    {
        $member = $this->userWithRole('member');
        $service = app(GameLevelProgressionService::class);
        $running = GameGoal::where('slug', 'level-6-running')->firstOrFail();

        $service->record($member, $running, 30, 8, 'activity');
        $runningProgress = $service->summaryFor($member)['levels']
            ->last()['goals']->firstWhere('goal.id', $running->id);

        $this->assertFalse($runningProgress['achieved']);
        $this->assertSame('30 minutes continuously at the configured required pace', $running->requirementLabel());
    }

    public function test_non_admin_cannot_open_or_change_game_configuration(): void
    {
        $member = $this->userWithRole('member');
        $goal = GameGoal::firstOrFail();

        $this->actingAs($member)->get(route('admin.game-levels.index'))->assertForbidden();
        $this->patch(route('admin.game-goals.update', $goal), $this->goalPayload($goal))->assertForbidden();
    }

    private function goalPayload(GameGoal $goal, array $overrides = []): array
    {
        return array_replace([
            'exercise_name' => $goal->exercise_name,
            'metric_type' => $goal->metric_type,
            'target_value' => $goal->target_value,
            'pace_target' => $goal->pace_target,
            'pace_unit' => $goal->pace_unit,
            'validation_method' => $goal->validation_method,
            'instructions' => $goal->instructions,
            'sort_order' => $goal->sort_order,
            'is_active' => $goal->is_active ? 1 : 0,
        ], $overrides);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
