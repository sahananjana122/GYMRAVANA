<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use HasFactory;

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_EVENT = 'event';

    public const TYPE_ACHIEVEMENT = 'achievement';

    public const TYPE_MONTHLY_HIGHLIGHT = 'monthly_highlight';

    public const TYPE_MONTHLY_CLIENT = 'monthly_client';

    public const TYPES = [
        self::TYPE_ANNOUNCEMENT => 'General announcement',
        self::TYPE_EVENT => 'Upcoming event',
        self::TYPE_ACHIEVEMENT => 'Achievement',
        self::TYPE_MONTHLY_HIGHLIGHT => 'Monthly highlight',
        self::TYPE_MONTHLY_CLIENT => 'Monthly best-performing client',
    ];

    protected $fillable = [
        'created_by',
        'member_id',
        'event_id',
        'photo_consent_confirmed_by',
        'type',
        'title',
        'slug',
        'summary',
        'body',
        'highlight_month',
        'progress_summary',
        'public_statistics',
        'cover_image_path',
        'before_image_path',
        'progress_image_path',
        'after_image_path',
        'photo_consent_confirmed',
        'photo_consent_confirmed_at',
        'is_featured',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'highlight_month' => 'date',
            'public_statistics' => 'array',
            'photo_consent_confirmed' => 'boolean',
            'photo_consent_confirmed_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function photoConsentConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photo_consent_confirmed_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? str($this->type)->headline()->toString();
    }

    public function imagePaths(): array
    {
        return array_values(array_filter([
            $this->cover_image_path,
            $this->before_image_path,
            $this->progress_image_path,
            $this->after_image_path,
        ]));
    }
}
