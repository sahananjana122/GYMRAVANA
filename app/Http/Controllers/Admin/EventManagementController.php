<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::query()->orderByDesc('starts_at')->get(),
            'eventTypes' => Event::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Event::create($validated + [
            'slug' => $this->slugFor($validated['title']),
        ]);

        return redirect()->route('admin.events.index')->with('status', 'Event created successfully.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $this->validated($request, $event);

        $event->update($validated + [
            'slug' => $this->slugFor($validated['title'], $event),
        ]);

        return redirect()->route('admin.events.index')->with('status', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('status', 'Event removed successfully.');
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:120',
                Rule::unique('events', 'title')->ignore($event),
            ],
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'venue' => ['required', 'string', 'max:180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function slugFor(string $title, ?Event $event = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $suffix = 2;

        while (Event::query()
            ->where('slug', $slug)
            ->when($event, fn ($query) => $query->whereKeyNot($event->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
