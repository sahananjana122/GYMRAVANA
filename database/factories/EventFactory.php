<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);
        $startsAt = fake()->dateTimeBetween('+1 week', '+4 months');

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'event_type' => fake()->randomElement(Event::TYPES),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(2, true),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+3 hours'),
            'venue' => fake()->streetAddress(),
            'capacity' => fake()->numberBetween(20, 200),
            'image_path' => null,
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function past(): static
    {
        return $this->state(function (): array {
            $startsAt = now()->subMonth();

            return [
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHours(3),
            ];
        });
    }
}
