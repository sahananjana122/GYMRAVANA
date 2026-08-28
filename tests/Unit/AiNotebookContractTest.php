<?php

namespace Tests\Unit;

use App\Services\Ai\ReadinessDatasetService;
use App\Services\Ai\ReadinessFeatureService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AiNotebookContractTest extends TestCase
{
    #[DataProvider('notebooks')]
    public function test_notebook_rejects_mismatched_or_incompatible_exports(string $notebook): void
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$notebook;
        $document = json_decode(
            file_get_contents($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $source = '';

        foreach ($document['cells'] as $cell) {
            $source .= implode('', $cell['source']);
        }

        $this->assertStringContainsString("metadata.get('schema_version') == 1", $source);
        $this->assertStringContainsString("metadata.get('dataset_sha256') == actual_hash", $source);
        $this->assertStringContainsString("metadata.get('row_count') == len(df)", $source);
        $this->assertStringContainsString("metadata.get('columns') == list(df.columns)", $source);
        $this->assertStringContainsString("metadata.get('target') == 'ready_for_progression'", $source);
        $this->assertStringContainsString('conflicting_member_months == 0', $source);
    }

    public static function notebooks(): array
    {
        return [
            'EDA notebook' => ['ai/notebooks/01_data_preparation_and_eda.ipynb'],
            'model-comparison notebook' => ['ai/notebooks/02_model_training_and_evaluation.ipynb'],
            'explainability and export notebook' => ['ai/notebooks/03_model_explainability_and_export.ipynb'],
        ];
    }

    public function test_model_selection_handoff_is_bound_to_the_exact_dataset(): void
    {
        $trainingNotebook = $this->notebookSource('ai/notebooks/02_model_training_and_evaluation.ipynb');
        $exportNotebook = $this->notebookSource('ai/notebooks/03_model_explainability_and_export.ipynb');

        $this->assertStringContainsString("'dataset_sha256': actual_hash", $trainingNotebook);
        $this->assertStringContainsString('SELECTION_REPORT_PATH.write_text', $trainingNotebook);
        $this->assertStringContainsString("selection_report.get('dataset_sha256') == actual_hash", $exportNotebook);
        $this->assertStringContainsString("selection_report.get('model_features') == MODEL_FEATURES", $exportNotebook);
    }

    public function test_final_model_artifacts_are_only_written_after_the_export_gate_passes(): void
    {
        $source = $this->notebookSource('ai/notebooks/03_model_explainability_and_export.ipynb');

        $this->assertStringContainsString('export_allowed = selection_report is not None and not block_reasons', $source);
        $this->assertStringContainsString("if export_allowed:\n    final_pipeline", $source);
        $this->assertStringContainsString("'readiness_model.joblib'", $source);
        $this->assertStringContainsString("'feature_schema.json'", $source);
        $this->assertStringContainsString("'model_metrics.json'", $source);
        $this->assertStringContainsString("'model_metadata.json'", $source);
        $this->assertStringContainsString('permutation_importance(', $source);
    }

    public function test_php_export_and_python_inference_share_the_same_feature_names(): void
    {
        $pythonContract = file_get_contents(
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'ai/service/artifacts.py',
        );
        $lastPosition = -1;

        foreach (ReadinessFeatureService::FEATURES as $feature) {
            $this->assertContains($feature, ReadinessDatasetService::HEADERS);
            $position = strpos($pythonContract, '"'.$feature.'"', $lastPosition + 1);
            $this->assertNotFalse($position, "Python inference contract is missing {$feature}.");
            $this->assertGreaterThan($lastPosition, $position, "Python feature order differs at {$feature}.");
            $lastPosition = $position;
        }
    }

    private function notebookSource(string $notebook): string
    {
        $document = json_decode(
            file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$notebook),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $source = '';

        foreach ($document['cells'] as $cell) {
            $source .= implode('', $cell['source']);
        }

        return $source;
    }
}
