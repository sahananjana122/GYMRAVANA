<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NoticeRequest;
use App\Models\Event;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NoticeManagementController extends Controller
{
    private const IMAGE_FIELDS = [
        'cover_image' => 'cover_image_path',
        'before_image' => 'before_image_path',
        'progress_image' => 'progress_image_path',
        'after_image' => 'after_image_path',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Notice::TYPES))],
            'status' => ['nullable', 'string', 'in:published,scheduled,unpublished'],
        ]);

        $notices = Notice::query()
            ->with(['creator', 'member', 'event'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, function ($query, string $status): void {
                match ($status) {
                    'published' => $query->published(),
                    'scheduled' => $query->where('is_published', true)->where('published_at', '>', now()),
                    'unpublished' => $query->where('is_published', false),
                };
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.notices.index', [
            'notices' => $notices,
            'types' => Notice::TYPES,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.notices.create', $this->formData());
    }

    public function store(NoticeRequest $request): RedirectResponse
    {
        $storedPaths = [];
        $data = $this->normalizedData($request);

        try {
            foreach (self::IMAGE_FIELDS as $input => $attribute) {
                if ($request->hasFile($input)) {
                    $data[$attribute] = $request->file($input)->store('notice-board', 'public');
                    $storedPaths[] = $data[$attribute];
                }
            }

            DB::transaction(function () use ($request, $data): void {
                Notice::create($data + [
                    'created_by' => $request->user()->id,
                    'slug' => $this->slugFor($data['title']),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        return redirect()->route('admin.notices.index')->with('status', 'Notice created successfully.');
    }

    public function edit(Notice $notice): View
    {
        return view('admin.notices.edit', $this->formData($notice) + ['notice' => $notice]);
    }

    public function update(NoticeRequest $request, Notice $notice): RedirectResponse
    {
        $storedPaths = [];
        $pathsToDelete = [];
        $data = $this->normalizedData($request, $notice);

        try {
            foreach (self::IMAGE_FIELDS as $input => $attribute) {
                if ($request->hasFile($input)) {
                    $data[$attribute] = $request->file($input)->store('notice-board', 'public');
                    $storedPaths[] = $data[$attribute];

                    if ($notice->{$attribute}) {
                        $pathsToDelete[] = $notice->{$attribute};
                    }
                } elseif ($request->boolean('remove_'.str_replace('_path', '', $attribute))) {
                    $data[$attribute] = null;

                    if ($notice->{$attribute}) {
                        $pathsToDelete[] = $notice->{$attribute};
                    }
                }
            }

            if ($data['type'] !== Notice::TYPE_MONTHLY_CLIENT) {
                foreach (['before_image_path', 'progress_image_path', 'after_image_path'] as $attribute) {
                    if ($notice->{$attribute}) {
                        $pathsToDelete[] = $notice->{$attribute};
                    }

                    $data[$attribute] = null;
                }

                if ($notice->type === Notice::TYPE_MONTHLY_CLIENT && ! $request->hasFile('cover_image')) {
                    if ($notice->cover_image_path) {
                        $pathsToDelete[] = $notice->cover_image_path;
                    }

                    $data['cover_image_path'] = null;
                }
            }

            DB::transaction(function () use ($data, $notice): void {
                $notice->update($data + [
                    'slug' => $this->slugFor($data['title'], $notice),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        Storage::disk('public')->delete(array_values(array_unique($pathsToDelete)));

        return redirect()->route('admin.notices.index')->with('status', 'Notice updated successfully.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $paths = $notice->imagePaths();
        $notice->delete();
        Storage::disk('public')->delete($paths);

        return redirect()->route('admin.notices.index')->with('status', 'Notice removed successfully.');
    }

    private function formData(?Notice $notice = null): array
    {
        return [
            'types' => Notice::TYPES,
            'members' => User::role('member')->orderBy('name')->get(['id', 'name', 'email']),
            'events' => Event::query()->orderByDesc('starts_at')->get(['id', 'title', 'starts_at']),
            'statisticsText' => $notice
                ? collect($notice->public_statistics)->map(fn ($value, $label) => "{$label}: {$value}")->implode(PHP_EOL)
                : '',
        ];
    }

    private function normalizedData(NoticeRequest $request, ?Notice $notice = null): array
    {
        $validated = Arr::except($request->validated(), [
            ...array_keys(self::IMAGE_FIELDS),
            'remove_cover_image',
            'remove_before_image',
            'remove_progress_image',
            'remove_after_image',
            'photo_consent_confirmed',
            'is_featured',
            'is_published',
        ]);

        $isMonthlyClient = $validated['type'] === Notice::TYPE_MONTHLY_CLIENT;
        $isPublished = $request->boolean('is_published');
        $consentConfirmed = $isMonthlyClient && $request->boolean('photo_consent_confirmed');

        $validated['event_id'] = $validated['type'] === Notice::TYPE_EVENT ? ($validated['event_id'] ?? null) : null;
        $validated['member_id'] = $isMonthlyClient ? ($validated['member_id'] ?? null) : null;
        $validated['highlight_month'] = $isMonthlyClient
            ? Carbon::createFromFormat('!Y-m', $validated['highlight_month'])->startOfMonth()
            : null;
        $validated['progress_summary'] = $isMonthlyClient ? ($validated['progress_summary'] ?? null) : null;
        $validated['public_statistics'] = $this->parseStatistics($validated['public_statistics'] ?? null);
        $validated['photo_consent_confirmed'] = $consentConfirmed;
        $validated['photo_consent_confirmed_at'] = $consentConfirmed
            ? ($notice?->photo_consent_confirmed_at ?? now())
            : null;
        $validated['photo_consent_confirmed_by'] = $consentConfirmed
            ? ($notice?->photo_consent_confirmed_by ?? $request->user()->id)
            : null;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $isPublished;
        $validated['published_at'] = $isPublished
            ? ($validated['published_at'] ?? $notice?->published_at ?? now())
            : null;

        return $validated;
    }

    private function parseStatistics(?string $statistics): ?array
    {
        if (! $statistics) {
            return null;
        }

        $parsed = [];
        $lines = preg_split('/\r\n|\r|\n/', $statistics) ?: [];

        foreach (array_filter(array_map('trim', $lines)) as $line) {
            [$label, $value] = array_map('trim', explode(':', $line, 2));
            $parsed[$label] = $value;
        }

        return $parsed ?: null;
    }

    private function slugFor(string $title, ?Notice $notice = null): string
    {
        $base = Str::slug($title) ?: 'notice';
        $slug = $base;
        $suffix = 2;

        while (Notice::query()
            ->where('slug', $slug)
            ->when($notice, fn ($query) => $query->whereKeyNot($notice->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
