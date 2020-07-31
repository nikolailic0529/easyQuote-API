<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Contract\ContractDraftedRepositoryInterface;
use App\Models\HpeContract;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Contract;
use App\Scopes\ContractTypeScope;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use Illuminate\Support\Facades\DB;

class ContractDraftedRepository extends SearchableRepository implements ContractDraftedRepositoryInterface
{
    protected Contract $contract;

    protected HpeContract $hpeContract;

    public function __construct(Contract $contract, HpeContract $hpeContract)
    {
        $this->contract = $contract;
        $this->hpeContract = $hpeContract;
    }

    public function paginate()
    {
        return parent::all();
    }

    public function userQuery(): Builder
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        $contractColumns = [
            'id',
            'user_id',
            'customer_id',
            'company_id',
            'quote_id',
            'hpe_contract_id',
            'hpe_contract_number',
            'hpe_contract_customer_name',
            'cached_relations',
            'document_type',
            'completeness',
            'created_at',
            'updated_at',
            'activated_at'
        ];

        $query = $this->contract->newQueryWithoutScope(ContractTypeScope::class)
            ->whereIn('document_type', [Q_TYPE_CONTRACT, Q_TYPE_HPE_CONTRACT]);

        $query = $query->select($contractColumns)
            ->when(
                /** If user is not super-admin we are retrieving the user's own contracts */
                $user->cant('view_contracts'),
                fn (Builder $query) => $query->currentUser()
                    /** Adding contracts that have been granted access to */
                    ->orWhereIn('quote_id', $user->getPermissionTargets('quotes.read'))
                    ->orWhereIn('user_id', $user->getModulePermissionProviders('contracts.read'))
            )
            ->drafted();

        return $query;
    }

    public function find(string $id): Contract
    {
        return $this->contract->whereId($id)->firstOrFail();
    }

    public function delete(string $id): bool
    {
        return tap(
            $this->find($id),
            fn (Contract $contract) => $contract->quote->update(['contract_template_id' => null])
        )->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    public function submit(string $id, array $attributes = []): bool
    {
        return tap($this->find($id))->update($attributes)->submit();
    }

    protected function filterQueryThrough(): array
    {
        return [
            app(\App\Http\Query\DefaultOrderBy::class, ['column' => 'updated_at']),
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\Quote\OrderByName::class,
            \App\Http\Query\Quote\OrderByCompanyName::class,
            \App\Http\Query\Quote\OrderByRfq::class,
            \App\Http\Query\Quote\OrderByValidUntil::class,
            \App\Http\Query\Quote\OrderBySupportStart::class,
            \App\Http\Query\Quote\OrderBySupportEnd::class,
            \App\Http\Query\Quote\OrderByCompleteness::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->with('usingVersion:id,updated_at', 'customer:id,rfq')->activated(),
            $this->userQuery()->with('usingVersion:id,updated_at', 'customer:id,rfq')->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->contract;
    }

    protected function searchableQuery()
    {
        return $this->userQuery()->with('usingVersion:id,updated_at', 'customer:id,rfq');
    }

    protected function searchableFields(): array
    {
        return [
            'company_name^5',
            'contract_number^5',
            'customer_name^5',
            'customer_valid_until^5',
            'customer_rfq^5',
            'user_fullname^4',
            'created_at^1'
        ];
    }
}
