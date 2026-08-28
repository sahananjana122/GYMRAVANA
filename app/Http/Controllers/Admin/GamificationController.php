<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AchievementRequest;
use App\Http\Requests\Admin\GamificationMissionRequest;
use App\Models\Achievement;
use App\Models\GamificationMission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GamificationController extends Controller
{
    public function index(): View
    {
        return view('admin.gamification.index', [
            'missions' => GamificationMission::query()
                ->withCount(['participations', 'participations as completion_count' => fn ($query) => $query->whereNotNull('completed_at')])
                ->orderByDesc('created_at')
                ->get(),
            'achievements' => Achievement::query()->withCount('unlocks')->orderBy('sort_order')->orderBy('title')->get(),
            'missionKinds' => GamificationMission::KINDS,
            'missionStatuses' => GamificationMission::STATUSES,
            'missionMetricLabels' => GamificationMission::metricLabels(),
            'achievementMetricLabels' => Achievement::metricLabels(),
        ]);
    }

    public function storeMission(GamificationMissionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        GamificationMission::create($data + [
            'slug' => $this->missionSlug($data['title']),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.gamification.index')->with('status', 'Mission created successfully.');
    }

    public function updateMission(
        GamificationMissionRequest $request,
        GamificationMission $gamificationMission,
    ): RedirectResponse {
        $data = $request->validated();

        if ($gamificationMission->participations()->exists() && $this->missionRulesChanged($gamificationMission, $data)) {
            return back()->withErrors([
                'mission' => 'The type, metric, target, reward and date window are locked after the first member joins. You may still edit the wording or publication status.',
            ]);
        }

        $gamificationMission->update($data + [
            'slug' => $this->missionSlug($data['title'], $gamificationMission),
        ]);

        return redirect()->route('admin.gamification.index')->with('status', 'Mission updated successfully.');
    }

    public function destroyMission(GamificationMission $gamificationMission): RedirectResponse
    {
        if ($gamificationMission->participations()->exists()) {
            return back()->withErrors([
                'mission' => 'A mission with member participation cannot be deleted. Change its status to archived instead.',
            ]);
        }

        $gamificationMission->delete();

        return redirect()->route('admin.gamification.index')->with('status', 'Unused mission removed successfully.');
    }

    public function storeAchievement(AchievementRequest $request): RedirectResponse
    {
        $data = $this->achievementData($request);

        Achievement::create($data + [
            'slug' => $this->achievementSlug($data['title']),
        ]);

        return redirect()->route('admin.gamification.index')->with('status', 'Achievement created successfully.');
    }

    public function updateAchievement(
        AchievementRequest $request,
        Achievement $achievement,
    ): RedirectResponse {
        $data = $this->achievementData($request);

        if ($achievement->unlocks()->exists()
            && ($achievement->metric !== $data['metric'] || $achievement->threshold !== (int) $data['threshold'])) {
            return back()->withErrors([
                'achievement' => 'The metric and threshold are locked after the first member unlocks this achievement.',
            ]);
        }

        $achievement->update($data + [
            'slug' => $this->achievementSlug($data['title'], $achievement),
        ]);

        return redirect()->route('admin.gamification.index')->with('status', 'Achievement updated successfully.');
    }

    public function destroyAchievement(Achievement $achievement): RedirectResponse
    {
        if ($achievement->unlocks()->exists()) {
            return back()->withErrors([
                'achievement' => 'An unlocked achievement cannot be deleted. Make it inactive to preserve member history.',
            ]);
        }

        $achievement->delete();

        return redirect()->route('admin.gamification.index')->with('status', 'Unused achievement removed successfully.');
    }

    private function achievementData(AchievementRequest $request): array
    {
        return $request->safe()->except('is_active') + [
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function missionRulesChanged(GamificationMission $mission, array $data): bool
    {
        foreach (['kind', 'metric', 'target_value', 'reward_xp', 'starts_on', 'ends_on'] as $field) {
            $current = $mission->{$field};
            $incoming = $data[$field] ?? null;

            if (in_array($field, ['starts_on', 'ends_on'], true)) {
                $current = $current?->toDateString();
                $incoming = $incoming ? Carbon::parse($incoming)->toDateString() : null;
            } elseif (in_array($field, ['target_value', 'reward_xp'], true)) {
                $current = (int) $current;
                $incoming = (int) $incoming;
            }

            if ($current !== $incoming) {
                return true;
            }
        }

        return false;
    }

    private function missionSlug(string $title, ?GamificationMission $ignored = null): string
    {
        return $this->uniqueSlug($title, GamificationMission::query(), $ignored);
    }

    private function achievementSlug(string $title, ?Achievement $ignored = null): string
    {
        return $this->uniqueSlug($title, Achievement::query(), $ignored);
    }

    private function uniqueSlug(string $title, $query, $ignored = null): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ((clone $query)
            ->where('slug', $slug)
            ->when($ignored, fn ($builder) => $builder->whereKeyNot($ignored->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
