<?php

namespace App\Domain\Mail\Resources\V1;

use App\Domain\Mail\Models\MailLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MailLog
 */
class MailLogResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'message_id' => $this->message_id,
            'from' => array_keys($this->from ?? []),
            'to' => array_keys($this->to ?? []),
            'subject' => $this->subject,
            'body' => $this->body,
            'sent_at' => $this->sent_at,
        ];
    }
}
