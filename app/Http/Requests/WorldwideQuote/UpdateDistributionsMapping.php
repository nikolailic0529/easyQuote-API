<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\DistributionMappingCollection;
use App\DTO\QuoteStages\MappingStage;
use App\Enum\ContractQuoteStage;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\Template\TemplateField;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDistributionsMapping extends FormRequest
{
    protected ?MappingStage $mappingStage = null;

    protected ?DistributionMappingCollection $distributionMappingCollection = null;

    protected ?Collection $requiredFields = null;

    protected ?WorldwideQuote $worldwideQuoteModel = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_distributions.*' => [
                'bail', 'required', 'array'
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')
                    ->where('worldwide_quote_id', $this->getQuote()->active_version_id)
                    ->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.mapping' => [
                'bail', 'required', 'array',
                function (string $attribute, array $value, Closure $fail) {
                    $mapping = Arr::pluck($value, 'importable_column_id', 'template_field_id');

                    foreach ($this->getRequiredFields() as $field) {
                        if (array_key_exists($field->getKey(), $mapping)) {
                            continue;
                        }

                        $fail("The field '$field->header' must be present in mapping.");
                    }
                }
            ],
            'worldwide_distributions.*.mapping.*.template_field_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(TemplateField::class, 'id'),
            ],
            'worldwide_distributions.*.mapping.*.importable_column_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(ImportableColumn::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.mapping.*.is_default_enabled' => [
                'bail', 'nullable', 'boolean',
            ],
            'worldwide_distributions.*.mapping.*.is_editable' => [
                'bail', 'nullable', 'boolean',
            ],
            'worldwide_distributions.*.mapping.*.is_preview_visible' => [
                'bail', 'nullable', 'boolean',
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels())
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->worldwideQuoteModel ??= with(true, function (): WorldwideQuote {
            /** @var WorldwideQuoteVersion $version */
            $version = WorldwideQuoteVersion::query()->whereHas('worldwideDistributions', function (Builder $builder) {
                $builder->whereKey($this->input('worldwide_distributions.*.id'));
            })->sole();

            return $version->worldwideQuote;
        });
    }

    public function getStage(): MappingStage
    {
        return $this->mappingStage ??= new MappingStage([
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage')),
            'mapping' => $this->getMappingCollection()
        ]);
    }

    public function getMappingCollection(): DistributionMappingCollection
    {
        return $this->distributionMappingCollection ??= DistributionMappingCollection::fromArray($this->getArrayableMapping());
    }

    /** @return Collection<TemplateField>|null */
    protected function getRequiredFields(): Collection
    {
        return $this->requiredFields ??= TemplateField::where('is_system', true)
            ->where('is_required', true)
            ->get(['id', 'name', 'header']);
    }

    protected function getArrayableMapping(): array
    {
        return collect($this->input('worldwide_distributions.*'))
            ->reduce(function (array $values, array $distribution) {
                foreach ($distribution['mapping'] as $mapping) {
                    array_push($values, [
                        'worldwide_distribution_id' => $distribution['id'],
                        'template_field_id' => $mapping['template_field_id'],
                        'importable_column_id' => $mapping['importable_column_id'],
                        'is_default_enabled' => (bool)($mapping['is_default_enabled'] ?? false),
                        'is_preview_visible' => (bool)($mapping['is_preview_visible'] ?? false),
                        'is_editable' => (bool)($mapping['is_editable'] ?? false)
                    ]);
                }

                return $values;
            }, []);
    }
}
