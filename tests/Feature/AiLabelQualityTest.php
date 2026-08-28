<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\Ai\DataReadinessService;
use App\Services\Ai\LabelQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AiLabelQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-08-26 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_conflicting_member_month_labels_block_training_and_are_visible_to_admins(): void
    {
        config()->set('ai_readiness', [
            'minimum_rows' => 2,
            'minimum_rows_per_class' => 1,
            'minimum_member_groups' => 1,
            'minimum_member_groups_per_class' => 1,
        ]);
        [$readyTrainer, $notReadyTrainer] = TrainerProfile::approved()->with('user')->take(2)->get();
        $member = User::factory()->create(['name' => 'Conflicting Label Member']);
        $member->assignRole('member');
        MemberProfile::create(['user_id' => $member->id, 'status' => 'active']);
        $this->assign($readyTrainer, $member);
        $this->assign($notReadyTrainer, $member);

        $this->actingAs($readyTrainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'ready_for_progression' => true,
                'readiness_rationale' => 'Consistent completed activity supports progression.',
            ])->assertSessionHasNoErrors();
        $this->actingAs($notReadyTrainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'ready_for_progression' => false,
                'readiness_rationale' => 'More consistent observed activity is still required.',
            ])->assertSessionHasNoErrors();

        $quality = app(LabelQualityService::class)->report();
        $readiness = app(DataReadinessService::class)->summary();

        $this->assertSame(1, $quality['conflict_count']);
        $this->assertTrue($quality['has_blocking_issues']);
        $this->assertSame(0, $quality['short_rationale_count']);
        $this->assertSame(50.0, $quality['dominant_trainer_share']);
        $this->assertSame(50.0, $quality['minority_class_share']);
        $this->assertTrue(collect($readiness['checks'])->every('met'));
        $this->assertFalse($readiness['training_allowed']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->get(route('admin.ai-readiness.index'))
            ->assertOk()
            ->assertSee('Contradictory ground truth')
            ->assertSee('Conflicting Label Member')
            ->assertSee($readyTrainer->user->name)
            ->assertSee($notReadyTrainer->user->name)
            ->assertSee('Do not train until investigated')
            ->assertDontSee('Consistent completed activity supports progression.')
            ->assertDontSee('More consistent observed activity is still required.');
    }

    private function assign(TrainerProfile $trainer, User $member): void
    {
        TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => now()->subDays(2),
            'confirmed_start_at' => now()->subDay(),
            'duration_minutes' => 60,
            'required_arrival_at' => now()->subDay()->subMinutes(15),
            'status' => TrainerBooking::STATUS_COMPLETED,
        ]);
    }
}
