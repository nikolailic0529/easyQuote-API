<?php

namespace App\Domain\DocumentEngine\Models;

use Illuminate\Contracts\Support\Responsable;

final class DocumentEngineEventHandleResult implements \JsonSerializable, Responsable
{
    const ACCEPTED = 'accepted';
    const IGNORED = 'ignored';

    public function __construct(protected string $result, protected ?string $reason = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $merge = [];

        if (false === is_null($this->reason)) {
            $merge['reason'] = $this->reason;
        }

        return [
                'result' => $this->result,
            ] + $merge;
    }

    /**
     * {@inheritDoc}
     */
    public function toResponse($request)
    {
        return response()->json(
            data: $this->jsonSerialize(),
            status: match ($this->result) {
                self::IGNORED => 422,
                default => 202,
            },
        );
    }
}
