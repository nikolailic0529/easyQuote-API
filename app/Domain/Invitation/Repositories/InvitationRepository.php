<?php

namespace App\Domain\Invitation\Repositories;

use App\Domain\Invitation\Models\Invitation;
use App\Domain\Shared\Eloquent\Repository\SearchableRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvitationRepository extends SearchableRepository implements \App\Domain\Invitation\Contracts\InvitationRepositoryInterface
{
    protected Invitation $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function userQuery(): Builder
    {
        return $this->invitation->query()->with('role');
    }

    public function find(string $token): Invitation
    {
        return $this->invitation->query()
            ->whereInvitationToken($token)->firstOrFail();
    }

    public function resend(string $token): bool
    {
        return $this->find($token)->resend();
    }

    public function cancel(string $token): bool
    {
        return $this->find($token)->cancel();
    }

    public function delete(string $token): bool
    {
        return $this->find($token)->delete();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Domain\Invitation\Queries\Filters\OrderByCreatedAt::class,
            \App\Domain\Invitation\Queries\Filters\OrderByEmail::class,
            \App\Domain\Invitation\Queries\Filters\OrderByRole::class,
            \App\Domain\Invitation\Queries\Filters\OrderByExpiresAt::class,
            \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy::class,
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->nonExpired(),
            $this->userQuery()->expired(),
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->invitation;
    }

    protected function searchableFields(): array
    {
        return [
            'email^5', 'role_name^4', 'created_at^3',
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('role');
    }
}
