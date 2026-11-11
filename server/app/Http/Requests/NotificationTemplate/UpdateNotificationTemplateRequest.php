<?php

namespace App\Http\Requests\NotificationTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('notifications.manage');
    }

    public function rules(): array
    {
        $isEmail = $this->route('notification_template')?->channel === 'email';

        return [
            'subject' => $isEmail
                ? ['required', 'string', 'max:255']
                : ['prohibited'],
            'body' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
