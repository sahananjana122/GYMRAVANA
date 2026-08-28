<?php

namespace App\Console\Commands;

use App\Services\Ai\ReadinessDatasetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SplFileObject;

class ExportReadinessDataset extends Command
{
    protected $signature = 'gymravana:export-readiness-data
        {--output=ai/data/readiness_dataset.csv : Project-relative CSV destination}';

    protected $description = 'Export pseudonymized, genuinely labeled progression-readiness rows for local notebook analysis';

    public function handle(ReadinessDatasetService $dataset): int
    {
        $relativePath = str_replace('\\', '/', trim((string) $this->option('output')));

        if (! $this->isSafeCsvPath($relativePath)) {
            $this->error('The output must be a project-relative .csv path without parent-directory segments.');

            return self::FAILURE;
        }

        $path = base_path($relativePath);
        File::ensureDirectoryExists(dirname($path));
        $csv = new SplFileObject($path, 'w');
        $csv->fputcsv(ReadinessDatasetService::HEADERS, ',', '"', '', "\n");
        $rowCount = 0;
        $labelCounts = ['not_ready' => 0, 'ready' => 0];

        foreach ($dataset->rows() as $row) {
            $csv->fputcsv(
                array_map(fn (string $header) => $row[$header] ?? null, ReadinessDatasetService::HEADERS),
                ',',
                '"',
                '',
                "\n",
            );
            $rowCount++;
            $labelCounts[$row['ready_for_progression'] === 1 ? 'ready' : 'not_ready']++;
        }

        $csv = null;
        $datasetHash = hash_file('sha256', $path);

        if ($datasetHash === false) {
            $this->error('The exported CSV could not be fingerprinted.');

            return self::FAILURE;
        }

        $metadataPath = substr($path, 0, -4).'.metadata.json';
        File::put($metadataPath, json_encode([
            'schema_version' => ReadinessDatasetService::SCHEMA_VERSION,
            'generated_at' => now()->toIso8601String(),
            'dataset' => $relativePath,
            'dataset_sha256' => $datasetHash,
            'row_count' => $rowCount,
            'label_counts' => $labelCounts,
            'has_both_classes' => $labelCounts['not_ready'] > 0 && $labelCounts['ready'] > 0,
            'target' => ReadinessDatasetService::TARGET,
            'columns' => ReadinessDatasetService::HEADERS,
            'source' => 'Trainer-recorded monthly progression-readiness assessments from GymRAVANA.',
            'privacy' => 'Pseudonymized behavioral training data only. No identity, medical, therapy, photograph, free-text, or body-measurement fields.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

        $this->info("Exported {$rowCount} labeled readiness row(s) to {$relativePath}.");

        if ($rowCount === 0) {
            $this->warn('The CSV contains headers only because no genuine trainer readiness labels exist yet.');
        } elseif ($labelCounts['not_ready'] === 0 || $labelCounts['ready'] === 0) {
            $this->warn('Only one target class is present. Do not train a classifier until both classes have genuine examples.');
        }

        return self::SUCCESS;
    }

    private function isSafeCsvPath(string $path): bool
    {
        return $path !== ''
            && str_ends_with(strtolower($path), '.csv')
            && ! str_starts_with($path, '/')
            && preg_match('/^[A-Za-z]:/', $path) !== 1
            && ! in_array('..', explode('/', $path), true);
    }
}
