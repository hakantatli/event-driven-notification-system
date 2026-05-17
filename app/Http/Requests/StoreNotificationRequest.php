<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => 'required|string|in:sms,email,push',
            'recipient' => 'required|string',
            'content' => 'required_without:template_name|string',
            'template_name' => 'required_without:content|exists:templates,name',
            'template_vars' => 'array',
            'priority' => 'string|in:low,normal,high',
            'scheduled_at' => 'nullable|date|after:now',
            'idempotency_key' => 'nullable|string|max:255',
        ];
    }
}
