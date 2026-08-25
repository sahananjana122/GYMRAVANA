<?php

namespace Database\Factories;

use App\Models\Notice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'type' => Notice::TYPE_ANNOUNCEMENT,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 9999),
            'summary' => fake()->sentence(14),
            'body' => fake()->paragraphs(2, true),
            'public_statistics' => null,
            'photo_consent_confirmed' => false,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'published_at' => now()->addWeek(),
        ]);
    }
}
