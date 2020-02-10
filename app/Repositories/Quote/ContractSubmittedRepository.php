<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\ContractSubmittedRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Contract;
use App\Repositories\Concerns\{
    ResolvesImplicitModel,
    ResolvesQuoteVersion
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};

class ContractSubmittedRepository extends SearchableRepository implements ContractSubmittedRepositoryInterface
{
    use ResolvesImplicitModel, ResolvesQuoteVersion;

    /** @var \App\Models\Quote\Contract */
    protected $contract;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    public function paginate()
    {
        return parent::all();
    }

    public function userQuery(): Builder
    {
        return $this->contract
            ->currentUserWhen(request()->user()->cant('view_contracts'))
            ->submitted();
    }

    public function find(string $id): Contract
    {
        return $this->contract->whereId($id)->firstOrFail();
    }

    public function delete(string $id): bool
    {
        return tap($this->find($id), function ($contract) {
            $contract->quote->update(['contract_template_id' => null]);
        })->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    public function unsubmit(string $id): bool
    {
        return $this->find($id)->unSubmit();
    }

    public function model(): string
    {
        return Contract::class;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
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
            $this->userQuery()->with('quote')->activated(),
            $this->userQuery()->with('quote')->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->contract;
    }

    protected function searchableQuery()
    {
        return $this->userQuery()->with('quote');
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
