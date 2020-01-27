<?php

namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface;
use App\Http\Requests\GetQuoteTemplatesRequest;
use App\Repositories\SearchableRepository;
use App\Models\QuoteTemplate\QuoteTemplate;
use App\Http\Requests\QuoteTemplate\UpdateQuoteTemplateRequest;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};
use Illuminate\Support\Collection as SupportCollection;
use Arr, \Closure;

class QuoteTemplateRepository extends SearchableRepository implements QuoteTemplateRepositoryInterface
{
    const DESIGN_ATTRIBUTES = ['form_data', 'complete_design'];

    /** @var \App\Models\QuoteTemplate\QuoteTemplate */
    protected $quoteTemplate;

    /** @var string */
    protected $table;

    public function __construct(QuoteTemplate $quoteTemplate)
    {
        $this->quoteTemplate = $quoteTemplate;
        $this->table = $quoteTemplate->getTable();
    }

    public function userQuery(): Builder
    {
        return $this->quoteTemplate->query()->with('company:id,name', 'vendor:id,name', 'countries:id,name');
    }

    public function allUserDefined(array $columns = ['*'], bool $cursor = false)
    {
        $method = $cursor ? 'cursor' : 'get';

        return $this->quoteTemplate->query()->where('is_system', false)->{$method}($columns);
    }

    public function find(string $id): QuoteTemplate
    {
        return $this->quoteTemplate->query()->whereId($id)->firstOrFail();
    }

    public function findByCompanyVendorCountry(GetQuoteTemplatesRequest $request): Collection
    {
        return $this->userQuery()
            ->where('quote_templates.company_id', $request->company_id)
            ->where('quote_templates.vendor_id', $request->vendor_id)
            ->join('country_quote_template', function ($join) use ($request) {
                $join->on('quote_templates.id', '=', 'country_quote_template.quote_template_id')
                    ->where('country_id', $request->country_id);
            })
            ->joinWhere('companies', 'companies.id', '=', $request->company_id)
            ->orderByRaw('field(`quote_templates`.`id`, `companies`.`default_template_id`, null) desc')
            ->select('quote_templates.*')
            ->get();
    }

    public function country(string $countryId): Collection
    {
        return $this->quoteTemplate->query()
            ->whereHas('countries', function ($query) use ($countryId) {
                $query->whereId($countryId);
            })->get();
    }

    public function random(int $limit = 1, ?Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->quoteTemplate->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof Closure) {
            $scope($query);
        }

        return $query->{$method}();
    }

    public function designer(string $id): SupportCollection
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

    public function create($request): QuoteTemplate
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return tap($this->quoteTemplate->create($request), function ($template) use ($request) {
            $template->syncCountries(data_get($request, 'countries'));
        });
    }

    public function update(UpdateQuoteTemplateRequest $request, string $id): QuoteTemplate
    {
        $attributes = $request->validated();

        $quoteTemplate = $this->find($id);
        $quoteTemplate->update($attributes);
        $quoteTemplate->syncCountries(data_get($attributes, 'countries'));

        /**
         * Determine that passed $attributes have only design related values (form_data & form_values_data).
         * Then if the passed $attributes have complete_design we'll perform record log with updated description.
         */
        $designKeys = ['form_data', 'complete_design'];

        if (
            Arr::has($attributes, $designKeys)
            && blank(array_diff_key($attributes, array_flip($designKeys)))
        ) {
            activity()
                ->on($quoteTemplate)
                ->queue('updated');
        }

        return $quoteTemplate->load('company', 'vendor', 'countries', 'templateFields', 'currency')->makeVisible(['form_data', 'form_values_data']);
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

    public function copy(string $id): array
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
                ->withProperties(['old' => QuoteTemplate::logChanges($replicatableTemplate), 'attributes' => QuoteTemplate::logChanges($template)])
                ->by(request()->user())
                ->queue('copied');
        }

        return ['id' => $template->id];
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
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->quoteTemplate;
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
