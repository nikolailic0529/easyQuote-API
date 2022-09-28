<?php

namespace App\Policies\Access;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Fluent;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @method DenyResponse action(string $action)
 * @method DenyResponse item(string $item)
 * @method DenyResponse reason(string $because)
 */
class DenyResponse extends Fluent
{
    public function toResponse(): Response
    {
        $action = $this->get('action', 'perform this action');
        $item = $this->get('item', '');
        $reason = $this->get('reason');

        $key = isset($reason) ? 'access.no_permissions_to_because' : 'access.no_permissions_to';

        return Response::deny(__($key, ['action' => $action, 'item' => $item, 'reason' => $reason]), HttpResponse::HTTP_FORBIDDEN);
    }
}