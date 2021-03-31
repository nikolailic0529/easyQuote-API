<?php

namespace App\Repositories\Quote;

use App\Builders\UnifiedContractBuilder;
use App\Contracts\Repositories\Contract\ContractDraftedRepositoryInterface;
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\HpeContract;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Contract;
use App\Models\User;
use App\Scopes\ContractTypeScope;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use Illuminate\Database\Query\JoinClause;
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

        $query = Contract::query()
            ->joinSub(
                Customer::select('customers.id', 'customers.rfq', 'customers.valid_until', 'customers.support_start', 'customers.support_end'),
                'customer',
                fn (JoinClause $join) => $join->on('customer.id', '=', 'contracts.customer_id')->limit(1)
            )
            ->joinSub(
                User::select('users.id', 'users.first_name', 'users.last_name'),
                'user',
                fn (JoinClause $join) => $join->on('user.id', 'contracts.user_id')->limit(1)
            )
            ->select(
                'contracts.id',
                DB::raw("2 as document_type"),
                'contracts.user_id',
                'user.first_name as user_first_name',
                'user.last_name as user_last_name',
                'contracts.customer_id',
                'customer.rfq as customer_rfq_number',
                'customer.valid_until as customer_valid_until_date',
                'customer.support_start as customer_support_start_date',
                'customer.support_end as customer_support_end_date',
                'contracts.company_id',
                'contracts.quote_id',
                'contracts.contract_number',
                'contracts.customer_name',
                'contracts.completeness',
                'contracts.created_at',
                'contracts.updated_at',
                'contracts.activated_at',
                'contracts.is_active'
            )
            ->addSelect([
                'company_name' => Company::select('name')->whereColumn('companies.id', 'contracts.company_id')->limit(1),
            ])
            ->when(
                /** If user is not super-admin we are retrieving the user's own contracts */
                $user->cant('view_contracts'),
                fn (Builder $query) => $query->currentUser()
                    /** Adding contracts that have been granted access to */
                    ->orWhereIn('quote_id', $user->getPermissionTargets('quotes.read'))
                    ->orWhereIn('user_id', $user->getModulePermissionProviders('contracts.read'))
            )
            ->whereNull('contracts.submitted_at');

        $query->unionAll(
            HpeContract::select(
                'hpe_contracts.id',
                DB::raw("3 as document_type"),
                'hpe_contracts.user_id',
                'user.first_name as user_first_name',
                'user.last_name as user_last_name',
                DB::raw('NULL as customer_id'),
                'hpe_contracts.contract_number as customer_rfq_number',
                DB::raw('NULL as customer_valid_until_date'),
                DB::raw('NULL as customer_support_start_date'),
                DB::raw('NULL as customer_support_end_date'),
                'hpe_contracts.company_id',
                DB::raw('NULL as quote_id'),
                'hpe_contracts.contract_number',
                'hpe_contracts.sold_contact->org_name as customer_name',
                'hpe_contracts.completeness',
                'hpe_contracts.created_at',
                'hpe_contracts.updated_at',
                'hpe_contracts.activated_at',
                'hpe_contracts.is_active'
            )
            ->joinSub(
                User::select('users.id', 'users.first_name', 'users.last_name'),
                'user',
                fn (JoinClause $join) => $join->on('user.id', '=', 'hpe_contracts.user_id')->limit(1)
            )
            ->addSelect([
                'company_name' => Company::select('name')->whereColumn('companies.id', 'hpe_contracts.company_id')->limit(1),
            ])
            ->when(
                /** If user is not super-admin we are retrieving the user's own contracts */
                $user->cant('view_contracts'),
                fn (Builder $query) => $query->currentUser()
                    /** Adding contracts that have been granted access to */
                    ->orWhereIn('user_id', $user->getModulePermissionProviders('contracts.read'))
            )
            ->whereNull('hpe_contracts.submitted_at')
        );

        return (new UnifiedContractBuilder($query->toBase()))->setModel(new Contract);
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
            \App\Http\Query\ActiveFirst::class,
            (new \App\Http\Query\OrderByCreatedAt)->qualifyColumnName(false),
            \App\Http\Query\Quote\OrderByName::class,
            \App\Http\Query\Quote\OrderByCompanyName::class,
            \App\Http\Query\Quote\OrderByRfq::class,
            \App\Http\Query\Quote\OrderByValidUntil::class,
            \App\Http\Query\Quote\OrderBySupportStart::class,
            \App\Http\Query\Quote\OrderBySupportEnd::class,
            \App\Http\Query\Quote\OrderByCompleteness::class,
            new \App\Http\Query\DefaultOrderBy('updated_at'),
        ];
    }

    protected function filterableQuery()
    {
        return $this->userQuery();
    }

    protected function searchableModel(): Model
    {
        return $this->contract;
    }

    protected function searchableQuery()
    {
        return $this->userQuery();
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
