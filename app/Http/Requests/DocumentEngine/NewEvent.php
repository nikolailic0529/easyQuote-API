<?php

namespace App\Http\Requests\DocumentEngine;

use App\DTO\DocumentEngine\DocumentEngineEventData;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\Passport\Client;

class NewEvent extends FormRequest
{
    protected ?DocumentEngineEventData $eventData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'event_reference' => [
                'required', 'string',
            ],
            'event_payload' => [
                'present', 'nullable', 'array',
            ],
            'causer_reference' => [
                'present', 'nullable', 'uuid',
            ],
        ];
    }

    public function getEventData(): DocumentEngineEventData
    {
        return $this->eventData ??= new DocumentEngineEventData([
            'event_reference' => $this->input('event_reference'),
            'causer_reference' => $this->input('causer_reference'),
            'event_payload' => $this->input('event_payload'),
        ]);
    }
}
