<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBatchNotificationRequest extends FormRequest
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
            'notifications' => 'required|array|min:1|max:1000',
            'notifications.*.channel' => 'required|string|in:sms,email,push',
            'notifications.*.recipient' => 'required|string',
            'notifications.*.content' => 'required_without:notifications.*.template_name|string',
            'notifications.*.template_name' => 'required_without:notifications.*.content|exists:templates,name',
            'notifications.*.template_vars' => 'array',
            'notifications.*.priority' => 'string|in:low,normal,high',
            'notifications.*.scheduled_at' => 'nullable|date|after:now',
            'notifications.*.idempotency_key' => 'nullable|string|max:255',
        ];
    }
}
