<?php

namespace App\Http\Requests\Admin;

use App\Models\GameLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'unlocks_master_gate' => $this->boolean('unlocks_master_gate'),
        ]);
    }

    public function rules(): array
    {
        /** @var GameLevel|null $level */
        $level = $this->route('gameLevel');

        return [
            'number' => [
                'required', 'integer', 'between:1,999',
                Rule::unique('game_levels', 'number')->ignore($level?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'unlocks_master_gate' => ['required', 'boolean'],
        ];
    }
}
