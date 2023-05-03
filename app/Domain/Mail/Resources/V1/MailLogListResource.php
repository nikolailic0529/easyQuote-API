<?php

namespace App\Domain\Mail\Resources\V1;

use App\Domain\Mail\Models\MailLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MailLog
 */
class MailLogListResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'from' => array_keys($this->from ?? []),
            'to' => array_keys($this->to ?? []),
            'subject' => $this->subject,
            'sent_at' => $this->sent_at,
        ];
    }
}
