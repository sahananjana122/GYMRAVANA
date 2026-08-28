<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ai\DataReadinessService;
use App\Services\Ai\ReadinessInferenceClient;
use App\Services\Ai\ReadinessPredictionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiReadinessController extends Controller
{
    public function index(
        DataReadinessService $readiness,
        ReadinessInferenceClient $inference,
        ReadinessPredictionService $predictions,
    ): View {
        $summary = $readiness->summary();

        return view('admin.ai-readiness.index', [
            'readiness' => $summary,
            'collectionPipeline' => $readiness->collectionPipeline($summary),
            'recentLabels' => $readiness->recentLabels(),
            'recentRevisions' => $readiness->recentRevisions(),
            'inferenceHealth' => $inference->health(),
            'predictionCandidates' => $predictions->candidates(),
        ]);
    }

    public function predict(User $member, ReadinessPredictionService $predictions): RedirectResponse
    {
        $outcome = $predictions->predictFor($member);

        if (! $outcome->succeeded) {
            return redirect()->route('admin.ai-readiness.index')
                ->withErrors(['prediction' => $outcome->message]);
        }

        return redirect()->route('admin.ai-readiness.index')
            ->with('status', $outcome->message);
    }
}
