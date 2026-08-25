<?php

namespace App\Http\Requests\Admin;

use App\Models\Notice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class NoticeRequest extends FormRequest
{
    private const IMAGE_FIELDS = [
        'cover_image' => 'cover_image_path',
        'before_image' => 'before_image_path',
        'progress_image' => 'progress_image_path',
        'after_image' => 'after_image_path',
    ];

    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Notice::TYPES))],
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:10000'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'member_id' => ['nullable', 'integer', 'exists:users,id'],
            'highlight_month' => ['nullable', 'date_format:Y-m'],
            'progress_summary' => ['nullable', 'string', 'max:5000'],
            'public_statistics' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'before_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'progress_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'after_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'remove_before_image' => ['nullable', 'boolean'],
            'remove_progress_image' => ['nullable', 'boolean'],
            'remove_after_image' => ['nullable', 'boolean'],
            'photo_consent_confirmed' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = $this->input('type');

            if ($type === Notice::TYPE_EVENT && ! $this->filled('event_id')) {
                $validator->errors()->add('event_id', 'Select the existing event connected to this notice.');
            }

            if ($type === Notice::TYPE_MONTHLY_CLIENT) {
                $this->validateMonthlyClient($validator);
            } elseif ($this->hasFile('before_image') || $this->hasFile('progress_image') || $this->hasFile('after_image')) {
                $validator->errors()->add('before_image', 'Progress photographs are only available for monthly client highlights.');
            }

            $this->validateStatistics($validator);
        }];
    }

    private function validateMonthlyClient(Validator $validator): void
    {
        if (! $this->filled('member_id')) {
            $validator->errors()->add('member_id', 'Select the member being featured.');
        } else {
            $member = User::find($this->integer('member_id'));

            if ($member && ! $member->hasRole('member')) {
                $validator->errors()->add('member_id', 'The featured account must have the member role.');
            }
        }

        if (! $this->filled('highlight_month')) {
            $validator->errors()->add('highlight_month', 'Select the month for this client highlight.');
        } else {
            $this->validateUniqueHighlightMonth($validator);
        }

        if (! $this->filled('progress_summary')) {
            $validator->errors()->add('progress_summary', 'Add the public progress summary approved for this highlight.');
        }

        if ($this->boolean('is_published') && $this->hasClientPhotos() && ! $this->boolean('photo_consent_confirmed')) {
            $validator->errors()->add(
                'photo_consent_confirmed',
                'Confirm the member\'s consent before publishing any client photograph.',
            );
        }
    }

    private function validateUniqueHighlightMonth(Validator $validator): void
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $this->input('highlight_month'))->startOfMonth();
        } catch (\Throwable) {
            return;
        }

        $notice = $this->route('notice');
        $alreadyExists = Notice::query()
            ->where('type', Notice::TYPE_MONTHLY_CLIENT)
            ->whereDate('highlight_month', $month)
            ->when($notice instanceof Notice, fn ($query) => $query->whereKeyNot($notice->getKey()))
            ->exists();

        if ($alreadyExists) {
            $validator->errors()->add('highlight_month', 'A monthly best-performing client is already selected for this month.');
        }
    }

    private function hasClientPhotos(): bool
    {
        $notice = $this->route('notice');

        foreach (self::IMAGE_FIELDS as $input => $attribute) {
            if ($this->hasFile($input)) {
                return true;
            }

            $removeInput = 'remove_'.str_replace('_path', '', $attribute);

            if ($notice instanceof Notice && $notice->{$attribute} && ! $this->boolean($removeInput)) {
                return true;
            }
        }

        return false;
    }

    private function validateStatistics(Validator $validator): void
    {
        if (! $this->filled('public_statistics')) {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $this->input('public_statistics')) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines)));

        if (count($lines) > 12) {
            $validator->errors()->add('public_statistics', 'Add no more than 12 public statistics.');

            return;
        }

        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                $validator->errors()->add('public_statistics', 'Write each statistic on a new line using Label: Value.');

                return;
            }

            [$label, $value] = array_map('trim', explode(':', $line, 2));

            if ($label === '' || $value === '' || mb_strlen($label) > 80 || mb_strlen($value) > 120) {
                $validator->errors()->add('public_statistics', 'Each statistic needs a short label and value.');

                return;
            }
        }
    }
}
