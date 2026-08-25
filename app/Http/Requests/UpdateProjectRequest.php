<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Deliberately without client_id: moving a project between clients would
     * also move a portal link's contents, and that is a different operation
     * with different consequences than editing a title.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        $attributes = $this->safe()->except('budget');

        $attributes['budget_cents'] = $this->filled('budget')
            ? (int) round(((float) $this->input('budget')) * 100)
            : null;

        return $attributes;
    }
}
