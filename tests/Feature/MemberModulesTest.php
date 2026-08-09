<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->member = User::factory()->create();
        $this->member->assignRole('member');
    }

    public function test_member_can_complete_a_workout_once_per_day(): void
    {
        $workout = WorkoutPlan::firstOrFail();

        $this->actingAs($this->member)->post(route('member.workouts.complete', $workout));
        $this->actingAs($this->member)->post(route('member.workouts.complete', $workout));

        $this->assertDatabaseCount('workout_completions', 1);
        $this->assertSame($workout->points, $this->member->totalPoints());
    }

    public function test_member_module_pages_are_rendered(): void
    {
        $this->actingAs($this->member)->get(route('member.dashboard'))->assertOk();
        $this->actingAs($this->member)->get(route('member.workouts.index'))->assertOk();
        $this->actingAs($this->member)->get(route('member.measurements.index'))->assertOk();
        $this->actingAs($this->member)->get(route('member.wellness.index'))->assertOk();
        $this->actingAs($this->member)->get(route('member.therapy.index'))->assertOk();
    }

    public function test_member_can_record_body_measurements(): void
    {
        $this->actingAs($this->member)->post(route('member.measurements.store'), [
            'recorded_on' => today()->toDateString(),
            'weight_kg' => 72.5,
            'height_cm' => 175,
            'waist_cm' => 82,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('body_measurements', [
            'user_id' => $this->member->id,
            'weight_kg' => 72.5,
        ]);
    }

    public function test_member_can_complete_a_wellness_activity(): void
    {
        $activity = WellnessActivity::firstOrFail();

        $this->actingAs($this->member)->post(route('member.wellness.complete', $activity));

        $this->assertDatabaseHas('wellness_completions', [
            'user_id' => $this->member->id,
            'wellness_activity_id' => $activity->id,
        ]);
    }

    public function test_member_can_submit_a_therapy_request(): void
    {
        $this->actingAs($this->member)->post(route('member.therapy.store'), [
            'subject' => 'General flexibility guidance',
            'symptoms' => 'I would like a gentle routine for improving flexibility.',
            'preferred_date' => now()->addWeek()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('therapy_requests', [
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);
    }
}
