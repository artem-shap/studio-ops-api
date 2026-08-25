<?php

namespace App\Http\Requests;

use App\Enums\MilestoneStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMilestoneRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:160'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(MilestoneStatus::class)],
        ];
    }
}
