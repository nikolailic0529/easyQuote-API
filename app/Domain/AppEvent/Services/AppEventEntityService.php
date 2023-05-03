<?php

namespace App\Domain\AppEvent\Services;

use App\Domain\AppEvent\Models\AppEvent;
use Illuminate\Database\ConnectionResolverInterface;

class AppEventEntityService
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver
    ) {
    }

    public function createAppEvent(
        string $name,
        \DateTimeInterface $occurrence = null,
        array $payload = null
    ): AppEvent {
        return tap(new AppEvent(), function (AppEvent $event) use ($name, $occurrence, $payload): void {
            $event->name = $name;
            $event->occurred_at = $occurrence ?? now();

            if ($payload !== null) {
                $event->payload = $payload;
            }

            $this->connectionResolver->connection()
                ->transaction(static fn () => $event->save());
        });
    }
}
