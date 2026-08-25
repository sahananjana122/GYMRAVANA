<?php

namespace Tests\Feature;

use App\Models\MemberPlan;
use App\Models\MemberProfile;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_trainer_dashboard_contains_the_four_phase_six_areas_and_only_assigned_clients(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $assigned = $this->member('Assigned Dashboard Client');
        $unrelated = $this->member('Unrelated Dashboard Client');
        $this->assign($trainer, $assigned);

        $this->actingAs($trainer->user)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Schedule, Workout & Meal Plans', false)
            ->assertSee('Booking Sessions')
            ->assertSeeInOrder(['03', 'Library'])
            ->assertSee('Monthly Tracker')
            ->assertSee($assigned->name)
            ->assertDontSee($unrelated->name);
    }

    public function test_trainer_can_create_a_structured_plan_for_an_assigned_member(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->assign($trainer, $member);

        $this->actingAs($trainer->user)
            ->post(route('trainer.plans.store'), $this->planPayload($member, [
                'title' => 'Assigned Strength Plan',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $plan = MemberPlan::where('title', 'Assigned Strength Plan')->firstOrFail();
        $this->assertSame($trainer->id, $plan->trainer_profile_id);
        $this->assertSame($trainer->user_id, $plan->created_by);
        $this->assertSame(MemberPlan::STATUS_ACTIVE, $plan->status);
        $this->assertSame(1, $plan->version);
        $this->assertNotNull($plan->assigned_at);
        $this->assertDatabaseHas('member_plan_items', [
            'member_plan_id' => $plan->id,
            'title' => 'Goblet squat practice',
            'day_of_week' => 1,
        ]);

        $this->actingAs($member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('Assigned Strength Plan')
            ->assertSee('Goblet squat practice');
    }

    public function test_saving_an_update_creates_history_instead_of_overwriting_the_plan(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->assign($trainer, $member);
        $this->actingAs($trainer->user)->post(route('trainer.plans.store'), $this->planPayload($member));
        $original = MemberPlan::latest('id')->firstOrFail();

        $payload = $this->planPayload($member, [
            'title' => 'Updated Strength Plan',
            'items' => [[
                'day_of_week' => 3,
                'scheduled_time' => '18:00',
                'section' => 'Strength',
                'title' => 'Split squat practice',
                'instructions' => 'Use a stable support.',
                'target' => '3 × 6 per side',
            ]],
        ]);
        unset($payload['member_id'], $payload['type']);

        $this->actingAs($trainer->user)
            ->patch(route('trainer.plans.update', $original), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $original->refresh();
        $updated = MemberPlan::where('supersedes_plan_id', $original->id)->firstOrFail();
        $this->assertSame(MemberPlan::STATUS_ARCHIVED, $original->status);
        $this->assertSame(2, $updated->version);
        $this->assertSame('Updated Strength Plan', $updated->title);
        $this->assertDatabaseHas('member_plan_items', ['member_plan_id' => $updated->id, 'title' => 'Split squat practice']);

        $this->actingAs($trainer->user)
            ->get(route('trainer.plans.show', $updated))
            ->assertOk()
            ->assertSee('Version history')
            ->assertSee('Version 2')
            ->assertSee('Version 1');

        $this->actingAs($member)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('Updated Strength Plan')
            ->assertSee('Split squat practice');
    }

    public function test_trainers_cannot_view_or_modify_plans_for_unassigned_or_other_trainers_clients(): void
    {
        [$owner, $otherTrainer] = TrainerProfile::approved()->take(2)->get();
        $member = $this->member();
        $this->assign($owner, $member);

        $this->actingAs($otherTrainer->user)
            ->post(route('trainer.plans.store'), $this->planPayload($member))
            ->assertForbidden();

        $this->actingAs($owner->user)->post(route('trainer.plans.store'), $this->planPayload($member));
        $plan = MemberPlan::latest('id')->firstOrFail();

        $this->actingAs($otherTrainer->user)->get(route('trainer.plans.show', $plan))->assertForbidden();
        $this->actingAs($otherTrainer->user)->get(route('trainer.plans.edit', $plan))->assertForbidden();
        $this->actingAs($member)->get(route('trainer.plans.index'))->assertForbidden();
    }

    public function test_library_uses_the_central_safe_external_configuration(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        config()->set('gymravana.library.url', 'https://drive.google.com/drive/folders/trainer-library');
        config()->set('gymravana.library.label', 'Shared Coaching Collection');

        $this->actingAs($trainer->user)
            ->get(route('trainer.library.index'))
            ->assertOk()
            ->assertSee('Shared Coaching Collection')
            ->assertSee('https://drive.google.com/drive/folders/trainer-library', false)
            ->assertSee('target="_blank"', false);

        config()->set('gymravana.library.url', 'javascript:alert(1)');
        $this->actingAs($trainer->user)
            ->get(route('trainer.library.index'))
            ->assertOk()
            ->assertSee('Library link not configured.')
            ->assertDontSee('javascript:alert(1)', false);
    }

    private function member(string $name = 'Plan Test Member'): User
    {
        $member = User::factory()->create(['name' => $name]);
        $member->assignRole('member');
        MemberProfile::create(['user_id' => $member->id, 'status' => 'active']);

        return $member;
    }

    private function assign(TrainerProfile $trainer, User $member): TrainerBooking
    {
        return TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => now()->addDay(),
            'confirmed_start_at' => now()->addDay(),
            'duration_minutes' => 60,
            'required_arrival_at' => now()->addDay()->subMinutes(15),
            'status' => TrainerBooking::STATUS_ACCEPTED,
        ]);
    }

    private function planPayload(User $member, array $overrides = []): array
    {
        return array_replace([
            'member_id' => $member->id,
            'type' => MemberPlan::TYPE_WORKOUT,
            'title' => 'Strength Foundation',
            'overview' => 'A safe structured plan.',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
            'status' => MemberPlan::STATUS_ACTIVE,
            'items' => [[
                'day_of_week' => 1,
                'scheduled_time' => '07:30',
                'section' => 'Strength',
                'title' => 'Goblet squat practice',
                'instructions' => 'Use controlled repetitions.',
                'target' => '3 × 8',
            ]],
        ], $overrides);
    }
}
