<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PoseAiNotebookContractTest extends TestCase
{
    public function test_pose_notebooks_define_a_separate_non_deployable_task(): void
    {
        $preparation = $this->notebookSource('ai/notebooks/04_pose_data_preparation.ipynb');
        $training = $this->notebookSource('ai/notebooks/05_pose_model_training_and_evaluation.ipynb');
        $export = $this->notebookSource('ai/notebooks/06_pose_explainability_and_prototype_export.ipynb');

        $this->assertStringContainsString('pose identity', $preparation);
        $this->assertStringContainsString('EXCLUDED_POSES', $preparation);
        $this->assertStringContainsString('source-grouped', $training);
        $this->assertStringContainsString("evaluation['deployment_allowed'] is False", $training);
        $this->assertStringContainsString("artifact['prototype_only'] is True", $export);
        $this->assertStringContainsString("artifact['deployment_allowed'] is False", $export);
        $this->assertStringContainsString('form correctness', $export);
    }

    public function test_public_retraining_notebook_preserves_the_evidence_boundary(): void
    {
        $notebook = $this->notebookSource('ai/notebooks/07_public_pose_retraining.ipynb');

        $this->assertStringContainsString('duplicate-grouped evaluation', $notebook);
        $this->assertStringContainsString("metadata['row_count'] >= 250", $notebook);
        $this->assertStringContainsString("evaluation['deployment_allowed'] is False", $notebook);
        $this->assertStringContainsString("artifact['prototype_only'] is True", $notebook);
        $this->assertStringContainsString('participant IDs', $notebook);
        $this->assertStringContainsString('progression readiness', $notebook);
    }

    public function test_pose_workflow_cannot_be_confused_with_progression_readiness(): void
    {
        $workflow = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'ai/pose/workflow.py');

        $this->assertIsString($workflow);
        $this->assertStringContainsString('five_class_yoga_pose_identity', $workflow);
        $this->assertStringContainsString('There is no trustworthy correct/incorrect form target.', $workflow);
        $this->assertStringContainsString('"progression readiness"', $workflow);
        $this->assertStringNotContainsString('ready_for_progression', $workflow);

        $publicWorkflow = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'ai/pose/public_workflow.py');

        $this->assertIsString($publicWorkflow);
        $this->assertStringContainsString('participant_ids_available', $publicWorkflow);
        $this->assertStringContainsString('trainer_verified_labels', $publicWorkflow);
        $this->assertStringContainsString('local_camera_test_available', $publicWorkflow);
        $this->assertStringContainsString('"deployment_allowed": False', $publicWorkflow);
        $this->assertStringNotContainsString('ready_for_progression', $publicWorkflow);
    }

    public function test_pose_inference_remains_local_validation_only_and_outside_member_routes(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'ai/service/main.py');
        $artifacts = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'ai/service/pose_artifacts.py');
        $cameraValidation = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'ai/pose/camera_validation.py');
        $routes = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'routes/web.php');

        $this->assertIsString($service);
        $this->assertIsString($artifacts);
        $this->assertIsString($cameraValidation);
        $this->assertIsString($routes);
        $this->assertStringContainsString('/v1/pose/validate', $service);
        $this->assertStringContainsString('loopback_required', $service);
        $this->assertStringContainsString('local_camera_validation_only', $artifacts);
        $this->assertStringContainsString('"deployment_allowed": False', $artifacts);
        $this->assertStringContainsString('participants.csv', $cameraValidation);
        $this->assertStringContainsString('consent_confirmed', $cameraValidation);
        $this->assertStringNotContainsString('pose/validate', $routes);
    }

    private function notebookSource(string $notebook): string
    {
        $document = json_decode(
            file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$notebook),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return collect($document['cells'])
            ->flatMap(fn (array $cell): array => $cell['source'])
            ->implode('');
    }
}
