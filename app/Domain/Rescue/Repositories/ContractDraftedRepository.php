<?php

namespace App\Domain\Rescue\Repositories;

use App\Domain\Company\Models\Company;
use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\Rescue\Contracts\ContractDraftedRepositoryInterface;
use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Shared\Eloquent\Repository\SearchableRepository;
use App\Domain\UnifiedContract\Builders\UnifiedContractBuilder;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        /** @var User $user */
        $user = auth()->user();

        $query = Contract::query()
            ->joinSub(
                Customer::select('customers.id', 'customers.rfq', 'customers.valid_until', 'customers.support_start', 'customers.support_end'),
                'customer',
                static fn (JoinClause $join) => $join->on('customer.id', '=', 'contracts.customer_id')->limit(1)
            )
            ->joinSub(
                User::select('users.id', 'users.first_name', 'users.last_name'),
                'user',
                static fn (JoinClause $join) => $join->on('user.id', 'contracts.user_id')->limit(1)
            )
            ->select(
                'contracts.id',
                DB::raw('2 as document_type'),
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
            ->whereNull('contracts.submitted_at');

        $query->unionAll(
            HpeContract::select(
                'hpe_contracts.id',
                DB::raw('3 as document_type'),
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
                    static fn (JoinClause $join) => $join->on('user.id', '=', 'hpe_contracts.user_id')->limit(1)
                )
                ->addSelect([
                    'company_name' => Company::select('name')->whereColumn('companies.id', 'hpe_contracts.company_id')->limit(1),
                ])
                ->whereNull('hpe_contracts.submitted_at')
        );

        return (new UnifiedContractBuilder($query->toBase()))->setModel(new \App\Domain\Rescue\Models\Contract());
    }

    public function find(string $id): Contract
    {
        return $this->contract->whereId($id)->firstOrFail();
    }

    public function delete(string $id): bool
    {
        return tap(
            $this->find($id),
            static fn (Contract $contract) => $contract->quote->update(['contract_template_id' => null])
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
            \App\Domain\Rescue\Queries\Filters\ActiveFirst::class,
            (new \App\Domain\Rescue\Queries\Filters\OrderByCreatedAt())->qualifyColumnName(false),
            \App\Domain\Rescue\Queries\Filters\OrderByName::class,
            \App\Domain\Rescue\Queries\Filters\OrderByCompanyName::class,
            \App\Domain\Rescue\Queries\Filters\OrderByRfq::class,
            \App\Domain\Rescue\Queries\Filters\OrderByValidUntil::class,
            \App\Domain\Rescue\Queries\Filters\OrderBySupportStart::class,
            \App\Domain\Rescue\Queries\Filters\OrderBySupportEnd::class,
            \App\Domain\Rescue\Queries\Filters\OrderByCompleteness::class,
            new \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy('updated_at'),
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
            'created_at^1',
        ];
    }
}
