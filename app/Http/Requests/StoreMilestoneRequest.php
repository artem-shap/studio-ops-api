<?php

namespace App\Http\Requests;

use App\Enums\MilestoneStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Position is not accepted from the client. A new milestone goes on the end,
     * and moving it is a separate, explicit action.
     *
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
