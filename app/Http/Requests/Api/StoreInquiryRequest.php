<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    /**
     * The name of the honeypot field. It is rendered hidden by CSS in
     * studio-ops-web, so a human never fills it and a bot usually does.
     */
    public const HONEYPOT = 'website';

    /**
     * Route access is settled by the shared-secret middleware; there is no user
     * to authorise here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * These limits are mirrored in the Zod schema in studio-ops-web. If the two
     * drift, a visitor passes client validation and then fails on the server.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'budget_range' => ['nullable', 'string', 'max:40'],

            // Present and empty. Filled means a bot, and it is rejected as an
            // ordinary validation failure so the sender learns nothing.
            self::HONEYPOT => ['present', 'prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            self::HONEYPOT.'.prohibited' => 'This submission could not be accepted.',
        ];
    }
}
