<?php

namespace App\Domain\Pipeliner\Requests;

use App\Domain\Pipeliner\DataTransferObjects\IncomingWebhookData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HandleWebhookEventRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            '*' => ['bail', 'required', 'array'],
            '*.webhook' => ['bail', 'required', 'array'],
            '*.webhook.id' => ['bail', 'required', 'uuid', Rule::exists(\App\Domain\Pipeliner\Models\PipelinerWebhook::class, 'pl_reference')->withoutTrashed()],
            '*.webhook.insecure_ssl' => ['bail', 'required', 'boolean'],
            '*.webhook.options' => ['bail', 'present', 'array'],
            '*.webhook.url' => ['bail', 'required', 'string'],
            '*.webhook.is_deleted' => ['bail', 'required', 'boolean'],
            '*.webhook.created' => ['bail', 'required', 'date'],
            '*.webhook.modified' => ['bail', 'required', 'date'],
            '*.webhook.application_id' => ['bail', 'required', 'uuid'],
            '*.webhook.client_id' => ['bail', 'present', 'nullable', 'uuid'],
            '*.webhook.events' => ['bail', 'required', 'array'],
            '*.webhook.events.*' => ['bail', 'required', 'string'],
            '*.event' => ['bail', 'required', 'string'],
            '*.event_time' => ['bail', 'required', 'date'],
            '*.team_space_id' => ['bail', 'required', 'string'],
            '*.related_entity' => ['bail', 'present'],
            '*.entity' => ['bail', 'required', 'array'],
            '*.entity.id' => ['bail', 'required', 'uuid'],
        ];
    }

    public function getIncomingWebhookData(): IncomingWebhookData
    {
        return new IncomingWebhookData(
            $this->input(0) + ['payload' => $this->input(0)],
        );
    }
}
