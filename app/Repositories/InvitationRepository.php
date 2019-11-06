<?php namespace App\Repositories;

use App\Contracts\Repositories\InvitationRepositoryInterface;
use App\Models\Collaboration\Invitation;
use Illuminate\Database\Eloquent \ {
    Model,
    Builder,
    Collection
};

class InvitationRepository extends SearchableRepository implements InvitationRepositoryInterface
{
    protected $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function userQuery(): Builder
    {
        return $this->invitation->query();
    }

    public function find(string $token): Invitation
    {
        return $this->userQuery()->whereInvitationToken($token)->firstOrFail();
    }

    public function resend(string $token): bool
    {
        return $this->find($token)->resend();
    }

    public function cancel(string $token): bool
    {
        return $this->find($token)->cancel();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\Invitation\OrderByEmail::class,
            \App\Http\Query\Invitation\OrderByRole::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->nonExpired(),
            $this->userQuery()->expired()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->invitation;
    }

    protected function searchableFields(): array
    {
        return [
            'email^5', 'role_name^4', 'created_at^3'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query;
    }
}
