<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\MonthlyProgressReview;
use App\Models\TrainerBooking;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WellnessActivity;
use App\Models\WellnessCompletion;
use App\Models\WorkoutCompletion;
use App\Models\WorkoutPlan;
use App\Services\Ai\ReadinessDatasetService;
use App\Services\Ai\ReadinessFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReadinessDatasetExportTest extends TestCase
{
    use RefreshDatabase;

    private string $csvPath;

    private string $metadataPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow('2026-08-25 12:00:00');
        $this->csvPath = storage_path('framework/testing/readiness-dataset.csv');
        $this->metadataPath = storage_path('framework/testing/readiness-dataset.metadata.json');
    }

    protected function tearDown(): void
    {
        File::delete([$this->csvPath, $this->metadataPath]);
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_exports_only_pseudonymized_labeled_behavioral_rows(): void
    {
        $trainer = TrainerProfile::approved()->firstOrFail();
        $member = $this->member();
        $this->completedSession($trainer, $member);

        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => WorkoutPlan::firstOrFail()->id,
            'completed_on' => '2026-07-05',
            'points_awarded' => 10,
        ]);
        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => WorkoutPlan::firstOrFail()->id,
            'completed_on' => '2026-08-05',
            'points_awarded' => 20,
        ]);
        WellnessCompletion::create([
            'user_id' => $member->id,
            'wellness_activity_id' => WellnessActivity::firstOrFail()->id,
            'completed_on' => '2026-08-06',
            'points_awarded' => 10,
        ]);
        $member->bodyMeasurements()->create([
            'recorded_on' => '2026-08-08',
            'weight_kg' => 81,
            'notes' => 'This private measurement note must never be exported.',
        ]);
        MonthlyProgressReview::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'review_month' => '2026-07-01',
            'goal_completion_percent' => 70,
            'rating' => 3,
            'assessment' => MonthlyProgressReview::ASSESSMENT_ON_TRACK,
        ]);

        $this->actingAs($trainer->user)
            ->put(route('trainer.tracker.update', $member), [
                'review_month' => '2026-08',
                'ready_for_progression' => true,
                'readiness_rationale' => 'Consistent attendance and completed sessions.',
            ])
            ->assertSessionHasNoErrors();

        WorkoutCompletion::create([
            'user_id' => $member->id,
            'workout_plan_id' => WorkoutPlan::firstOrFail()->id,
            'completed_on' => '2026-08-26',
            'points_awarded' => 99,
        ]);

        $this->artisan('gymravana:export-readiness-data', [
            '--output' => 'storage/framework/testing/readiness-dataset.csv',
        ])->assertSuccessful();

        $lines = file($this->csvPath, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $this->assertCount(2, $lines);
        $headers = str_getcsv($lines[0], ',', '"', '');
        $values = str_getcsv($lines[1], ',', '"', '');
        $row = array_combine($headers, $values);

        $this->assertSame(ReadinessDatasetService::HEADERS, $headers);
        $this->assertSame('1', $row['workout_completions']);
        $this->assertSame('1', $row['wellness_completions']);
        $this->assertSame('1', $row['trainer_sessions_completed']);
        $this->assertSame('30', $row['activity_points']);
        $this->assertSame('70', $row['previous_goal_completion']);
        $this->assertSame('on_track', $row['previous_assessment']);
        $this->assertSame('1', $row['ready_for_progression']);
        $this->assertSame(20, strlen($row['member_key']));
        $this->assertStringStartsWith('2026-08-25T12:00:00', $row['label_recorded_at']);

        $currentFeatures = app(ReadinessFeatureService::class)->currentFor($member, $trainer->id);
        $this->assertSame(ReadinessFeatureService::FEATURES, array_keys($currentFeatures));
        $this->assertSame(1, $currentFeatures['workout_completions']);
        $this->assertSame(1, $currentFeatures['wellness_completions']);
        $this->assertSame(30, $currentFeatures['activity_points']);
        $this->assertSame('on_track', $currentFeatures['previous_assessment']);
        $this->assertArrayNotHasKey('user_id', $currentFeatures);
        $this->assertArrayNotHasKey('readiness_rationale', $currentFeatures);

        $contents = File::get($this->csvPath);
        $this->assertStringNotContainsString($member->name, $contents);
        $this->assertStringNotContainsString($member->email, $contents);
        $this->assertStringNotContainsString('weight_kg', $contents);
        $this->assertStringNotContainsString('readiness_rationale', $contents);
        $this->assertStringNotContainsString('Consistent attendance and completed sessions.', $contents);
        $this->assertStringNotContainsString('This private measurement note must never be exported.', $contents);

        $metadata = json_decode(File::get($this->metadataPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(ReadinessDatasetService::SCHEMA_VERSION, $metadata['schema_version']);
        $this->assertSame(hash_file('sha256', $this->csvPath), $metadata['dataset_sha256']);
        $this->assertSame(1, $metadata['row_count']);
        $this->assertSame(['not_ready' => 0, 'ready' => 1], $metadata['label_counts']);
        $this->assertFalse($metadata['has_both_classes']);
        $this->assertSame(ReadinessDatasetService::TARGET, $metadata['target']);
        $this->assertSame(ReadinessDatasetService::HEADERS, $metadata['columns']);
    }

    public function test_command_writes_headers_only_when_no_genuine_labels_exist(): void
    {
        $this->artisan('gymravana:export-readiness-data', [
            '--output' => 'storage/framework/testing/readiness-dataset.csv',
        ])->assertSuccessful();

        $lines = file($this->csvPath, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);
        $this->assertSame(ReadinessDatasetService::HEADERS, str_getcsv($lines[0], ',', '"', ''));

        $metadata = json_decode(File::get($this->metadataPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(ReadinessDatasetService::SCHEMA_VERSION, $metadata['schema_version']);
        $this->assertSame(hash_file('sha256', $this->csvPath), $metadata['dataset_sha256']);
        $this->assertSame(0, $metadata['row_count']);
        $this->assertFalse($metadata['has_both_classes']);
    }

    public function test_command_rejects_paths_outside_the_project(): void
    {
        $this->artisan('gymravana:export-readiness-data', [
            '--output' => '../readiness-dataset.csv',
        ])->assertFailed();
    }

    private function member(): User
    {
        $member = User::factory()->create([
            'name' => 'Private Dataset Member',
            'email' => 'private-dataset-member@example.test',
        ]);
        $member->assignRole('member');
        MemberProfile::create(['user_id' => $member->id, 'status' => 'active']);

        return $member;
    }

    private function completedSession(TrainerProfile $trainer, User $member): void
    {
        TrainerBooking::create([
            'trainer_profile_id' => $trainer->id,
            'user_id' => $member->id,
            'program_type' => 'Personal training',
            'requested_datetime' => '2026-08-10 10:00:00',
            'confirmed_start_at' => '2026-08-10 10:00:00',
            'duration_minutes' => 60,
            'required_arrival_at' => '2026-08-10 09:45:00',
            'status' => TrainerBooking::STATUS_COMPLETED,
        ]);
    }
}
