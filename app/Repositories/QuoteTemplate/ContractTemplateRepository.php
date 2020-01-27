<?php

namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\ContractTemplateRepositoryInterface;
use App\Models\QuoteTemplate\ContractTemplate;
use App\Repositories\SearchableRepository;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection as EloquentCollection
};
use Illuminate\Support\Collection;
use Closure, Arr;

class ContractTemplateRepository extends SearchableRepository implements ContractTemplateRepositoryInterface
{
    const DESIGN_ATTRIBUTES = ['form_data', 'complete_design'];

    /** @var \App\QuoteTemplate\ContractTemplate */
    protected $template;

    public function __construct(ContractTemplate $template)
    {
        $this->template = $template;
    }

    public function query(): Builder
    {
        return $this->template->query();
    }

    public function paginate()
    {
        return parent::all();
    }

    public function find(string $id): ContractTemplate
    {
        return $this->template->whereId($id)->firstOrFail();
    }

    public function create(array $attributes): ContractTemplate
    {
        return tap($this->template->create($attributes), function ($template) use ($attributes) {
            $template->syncCountries(data_get($attributes, 'countries'));
        });
    }

    public function findByCompanyVendorCountry($request): EloquentCollection
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        $company_id = data_get($request, 'company_id');
        $vendor_id = data_get($request, 'vendor_id');
        $country_id = data_get($request, 'country_id');

        return $this->query()
            ->where('quote_templates.company_id', $company_id)
            ->where('quote_templates.vendor_id', $vendor_id)
            ->join('country_quote_template', function ($join) use ($country_id) {
                $join->on('quote_templates.id', '=', 'country_quote_template.quote_template_id')
                    ->where('country_id', $country_id);
            })
            ->joinWhere('companies', 'companies.id', '=', $company_id)
            ->orderByRaw('field(`quote_templates`.`id`, `companies`.`default_template_id`, null) desc')
            ->get();
    }

    public function country(string $countryId): EloquentCollection
    {
        return $this->query()
            ->whereHas('countries', function ($query) use ($countryId) {
                $query->whereId($countryId);
            })->get();
    }

    public function random(int $limit = 1, ?Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof Closure) {
            $scope($query);
        }

        return $query->{$method}();
    }

    public function designer(string $id): Collection
    {
        $template = $this->find($id)->load('company', 'vendor.image');

        $company_logos = $template->company->appendLogo()->logoDimensions ?? [];
        $vendor_logos = $template->vendor->appendLogo()->logoDimensions ?? [];

        $designer = collect(__('template.designer'))->transform(function ($page) {
            return collect($page)->transform(function ($tag) {
                return array_merge($tag, ['is_image' => false]);
            })->toArray();
        });

        $designer['first_page'] = array_merge($designer['first_page'], $company_logos, $vendor_logos);

        return $designer;
    }

    public function update(array $attributes, string $id): ContractTemplate
    {
        $template = tap($this->find($id), function ($template) use ($attributes) {
            $template->update($attributes);
            $template->syncCountries(data_get($attributes, 'countries'));
        });

        /**
         * Determine that passed $attributes have only design related values (self::DESIGN_ATTRIBUTES).
         * Then if the passed $attributes have complete_design we'll perform record log with updated description.
         */

        if (
            Arr::has($attributes, self::DESIGN_ATTRIBUTES)
            && blank(array_diff_key($attributes, array_flip(self::DESIGN_ATTRIBUTES)))
        ) {
            activity()->on($template)->queue('updated');
        }

        return $template;
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    public function copy(string $id)
    {
        activity()->disableLogging();

        $replicatableTemplate = $this->find($id);
        $template = $replicatableTemplate->replicate(['user', 'countries', 'templateFields']);
        $countries = $replicatableTemplate->countries->pluck('id')->toArray();
        $templateFields = $replicatableTemplate->templateFields->pluck('id')->toArray();

        $copied = $template->save();

        $copied && $template->syncCountries($countries) && $template->syncTemplateFields($templateFields);

        activity()->enableLogging();

        if ($copied) {
            activity()
                ->on($template)
                ->withProperties(['old' => ContractTemplate::logChanges($replicatableTemplate), 'attributes' => ContractTemplate::logChanges($template)])
                ->by(request()->user())
                ->queue('copied');
        }

        return ['id' => $template->id];
    }

    public function model(): string
    {
        return ContractTemplate::class;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\QuoteTemplate\OrderByCompanyName::class,
            \App\Http\Query\QuoteTemplate\OrderByVendorName::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->query()->activated(),
            $this->query()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->template;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'countries.name^4', 'vendor.name^4', 'company.name^4', 'created_at^3'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('company:id,name', 'vendor:id,name', 'countries:id,name');
    }
}
