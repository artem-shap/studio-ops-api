<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],

            // The form takes whole currency units, because nobody types cents
            // into a budget field. Storage is minor units; see attributes().
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3'],

            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * The shape the model wants, with the currency conversion done once, here,
     * rather than in each controller that happens to touch a project.
     *
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
