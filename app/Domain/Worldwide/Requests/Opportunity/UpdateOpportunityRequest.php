<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\ContractType\Models\ContractType;
use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Date\Enum\DateMonthEnum;
use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Models\PipelineStage;
use App\Domain\Pipeline\Queries\PipelineQueries;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\CreateOpportunityRecurrenceData;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\UpdateOpportunityData;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\OpportunitySupplier;
use App\Domain\Worldwide\Validation\Rules\ValidSupplierData;
use App\Foundation\Validation\Rules\ModelIsActive;
use App\Foundation\Validation\Rules\ScalarValue;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateOpportunityRequest extends FormRequest
{
    protected readonly ?Pipeline $defaultPipeline;

    protected ?UpdateOpportunityData $updateOpportunityData = null;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sales_unit_id' => [
                'bail', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed(),
            ],
            'pipeline_stage_id' => [
                'bail', 'uuid',
                Rule::exists(PipelineStage::class, 'id')->withoutTrashed()->where(function (Builder $builder): void {
                    $builder->where('pipeline_id', $this->input('pipeline_id', $this->getDefaultPipeline()->getKey()));
                }),
            ],
            'contract_type_id' => [
                'bail', 'uuid',
                Rule::exists(ContractType::class, 'id'),
            ],
            'primary_account_id' => [
                'bail', 'uuid',
                Rule::exists(Company::class, 'id')->withoutTrashed(),
                ModelIsActive::model(Company::class)
                    ->setMessage(__('The given primary account is inactive.')),
            ],
            'end_user_id' => [
                'bail', 'uuid',
                Rule::exists(Company::class, 'id')->withoutTrashed(),
                ModelIsActive::model(Company::class)
                    ->setMessage(__('The given end user is inactive.')),
            ],
            'are_end_user_addresses_available' => [
                'bail', 'boolean',
            ],
            'are_end_user_contacts_available' => [
                'bail', 'boolean',
            ],
            'primary_account_contact_id' => [
                'bail', 'uuid',
                Rule::exists(Contact::class, 'id')->withoutTrashed(),
            ],
            'account_manager_id' => [
                // The rule [required_with:project_name] is required to determine
                // if the update request was sent from the opportunity form.
                'bail', 'required_with:project_name', 'uuid',
                Rule::exists(User::class, 'id')->withoutTrashed(),
            ],
            'project_name' => [
                'bail', 'string', 'max:100',
                Rule::unique(Opportunity::class)
                    ->ignore($this->route('opportunity'))
                    ->withoutTrashed(),
            ],
            'nature_of_service' => [
                'bail', 'string', 'max:191',
            ],
            'renewal_month' => [
                'bail', 'string', 'max:191',
            ],
            'renewal_year' => [
                'bail', 'integer', 'max:9999',
            ],
            'customer_status' => [
                'bail', 'string', 'max:191',
            ],
            'end_user_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'hardware_status' => [
                'bail', 'string', 'max:191',
            ],
            'region_name' => [
                'bail', 'string', 'max:191',
            ],
            'opportunity_start_date' => [
                'bail',
                Rule::requiredIf(fn () => $this->has('is_contract_duration_checked') && ($this->boolean('is_contract_duration_checked') === false)),
                'date_format:Y-m-d',
            ],
            'is_opportunity_start_date_assumed' => [
                'bail', 'boolean',
            ],
            'opportunity_end_date' => [
                'bail',
                Rule::requiredIf(fn () => $this->has('is_contract_duration_checked') && ($this->boolean('is_contract_duration_checked') === false)),
                'date_format:Y-m-d',
            ],
            'is_opportunity_end_end_assumed' => [
                'bail', 'boolean',
            ],
            'opportunity_closing_date' => [
                'bail',
                Rule::requiredIf(fn () => $this->has('is_contract_duration_checked') && ($this->boolean('is_contract_duration_checked') === false)),
                'date_format:Y-m-d',
            ],
            'contract_duration_months' => [
                'bail',
                Rule::requiredIf(fn () => $this->has('is_contract_duration_checked') && $this->boolean('is_contract_duration_checked')),
                'integer', 'min:1', 'max:60',
            ],
            'is_contract_duration_checked' => [
                'bail', 'boolean',
            ],
            'expected_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'customer_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'purchase_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'supplier_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'supplier_order_transaction_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'supplier_order_confirmation_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'opportunity_amount' => [
                'bail', 'numeric',
            ],
            'opportunity_amount_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'purchase_price' => [
                'bail', 'numeric',
            ],
            'purchase_price_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'list_price' => [
                'bail', 'numeric',
            ],
            'list_price_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'estimated_upsell_amount' => [
                'bail', 'numeric',
            ],
            'estimated_upsell_amount_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'personal_rating' => [
                'bail', 'nullable', new ScalarValue(), 'filled',
            ],
            'ranking' => [
                'bail', 'nullable', 'integer', 'min:0', 'max:100',
            ],
            'margin_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'service_level_agreement_id' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'sale_unit_name' => [
                'bail', 'string', 'max:191',
            ],
            'competition_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'drop_in' => [
                'bail', 'string', 'max:191',
            ],
            'lead_source_name' => [
                'bail', 'string', 'max:191',
            ],
            'has_higher_sla' => [
                'bail', 'boolean',
            ],
            'is_multi_year' => [
                'bail', 'boolean',
            ],
            'has_additional_hardware' => [
                'bail', 'boolean',
            ],
            'remarks' => [
                'bail', 'nullable', 'string', 'max:10000',
            ],
            'notes' => [
                'bail', 'nullable', 'string', 'max:10000',
            ],
            'campaign_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers_grid' => [
                'bail', 'nullable', 'array', 'max:5',
            ],
            'suppliers_grid.*' => [
                'bail', 'array', new ValidSupplierData(),
            ],
            'suppliers_grid.*.id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(OpportunitySupplier::class, 'id')->where('opportunity_id', $this->route('opportunity')->getKey()),
            ],
            'suppliers_grid.*.supplier_name' => [
                'bail', 'nullable', 'string', 'max:100',
            ],
            'suppliers_grid.*.country_name' => [
                'bail', 'nullable', 'string', 'max:100',
            ],
            'suppliers_grid.*.contact_name' => [
                'bail', 'nullable', 'string', 'max:100',
            ],
            'suppliers_grid.*.contact_email' => [
                'bail', 'nullable', 'string', 'max:100',
            ],

            'recurrence.stage_id' => ['bail', 'required_with:recurrence', 'uuid',
                Rule::exists(PipelineStage::class, (new PipelineStage())->getKeyName())->withoutTrashed()],
            'recurrence.type' => ['bail', 'required_with:recurrence', new Enum(RecurrenceTypeEnum::class)],
            'recurrence.occur_every' => ['bail', 'required_with:recurrence', 'integer', 'min:1', 'max:99'],
            'recurrence.occurrences_count' => ['bail', 'required_with:recurrence', 'integer', 'min:-1', 'max:9999'],
            'recurrence.start_date' => ['bail', 'required_with:recurrence', 'date'],
            'recurrence.end_date' => ['bail', 'nullable', 'date', 'after:recurrence.start_date'],
            'recurrence.day' => ['bail', 'required_with:recurrence', new Enum(DateDayEnum::class)],
            'recurrence.month' => ['bail', 'required_with:recurrence', new Enum(DateMonthEnum::class)],
            'recurrence.week' => ['bail', 'required_with:recurrence', new Enum(DateWeekEnum::class)],
            'recurrence.day_of_week' => ['bail', 'required_with:recurrence', 'integer', 'min:1', 'max:127'],
            'recurrence.condition' => ['bail', 'required_with:recurrence', 'integer', 'min:1', 'max:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_name.unique' => 'The opportunity name [:input] already taken.',
            'account_manager_id.required_with' => 'Account manager must be selected.',
        ];
    }

    protected function getDefaultPipeline(): Pipeline
    {
        /** @var PipelineQueries $pipelineQueries */
        $pipelineQueries = $this->container[PipelineQueries::class];

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->defaultPipeline ??= $pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();
    }

    public function getOpportunityData(): UpdateOpportunityData
    {
        return $this->updateOpportunityData ??= with(true, function (): UpdateOpportunityData {
            /** @var \App\Domain\User\Models\User $user */
            $user = $this->user();
            $timezone = $user->timezone->utc ?? config('app.timezone');

            $data = [];

            $this->whenHas('suppliers_grid', static function (?array $input) use (&$data): void {
                $input = Arr::wrap($input);

                $data['create_suppliers'] = [];
                $data['update_suppliers'] = [];

                foreach ($input as $supplier) {
                    $array = [
                        'supplier_name' => $supplier['supplier_name'] ?? null,
                        'country_name' => $supplier['country_name'] ?? null,
                        'contact_name' => $supplier['contact_name'] ?? null,
                        'contact_email' => $supplier['contact_email'] ?? null,
                    ];

                    if (isset($supplier['id'])) {
                        $array['supplier_id'] = $supplier['id'];
                        $data['update_suppliers'][] = $array;
                    } else {
                        $data['create_suppliers'][] = $array;
                    }
                }
            });

            $this->whenHas('recurrence', function (array $input) use (&$data, $timezone): void {
                $data['recurrence'] = new CreateOpportunityRecurrenceData([
                    'stage_id' => $this->input('recurrence.stage_id'),
                    'type' => RecurrenceTypeEnum::tryFrom($this->input('recurrence.type')),
                    'occur_every' => $this->input('recurrence.occur_every'),
                    'occurrences_count' => $this->input('recurrence.occurrences_count'),
                    'condition' => $this->input('recurrence.condition'),
                    'start_date' => $this->date('recurrence.start_date', tz: $timezone)
                        ->tz(config('app.timezone'))
                        ->toDateTimeImmutable(),
                    'end_date' => $this->date('recurrence.end_date', tz: $timezone)
                        ?->tz(config('app.timezone'))
                        ?->toDateTimeImmutable(),
                    'day' => DateDayEnum::tryFrom($this->input('recurrence.day')),
                    'month' => DateMonthEnum::tryFrom($this->input('recurrence.month')),
                    'week' => DateWeekEnum::tryFrom($this->input('recurrence.week')),
                    'day_of_week' => (int) $this->input('recurrence.day_of_week'),
                ]);
            });

            $attributes = [
                'sales_unit_id',
                'pipeline_stage_id',
                'contract_type_id',
                'account_manager_id',
                'primary_account_id',
                'end_user_id',
                'are_end_user_addresses_available',
                'are_end_user_contacts_available',
                'primary_account_contact_id',
                'project_name',
                'nature_of_service',
                'renewal_month',
                'renewal_year',
                'customer_status',
                'end_user_name',
                'hardware_status',
                'region_name',
                'opportunity_start_date',
                'is_opportunity_start_date_assumed',
                'opportunity_end_date',
                'is_opportunity_end_date_assumed',
                'opportunity_closing_date',
                'is_contract_duration_checked',
                'contract_duration_months',
                'expected_order_date',
                'customer_order_date',
                'purchase_order_date',
                'supplier_order_date',
                'supplier_order_transaction_date',
                'supplier_order_confirmation_date',
                'opportunity_amount',
                'opportunity_amount_currency_code',
                'purchase_price',
                'purchase_price_currency_code',
                'list_price',
                'list_price_currency_code',
                'estimated_upsell_amount',
                'estimated_upsell_amount_currency_code',
                'personal_rating',
                'ranking',
                'margin_value',
                'service_level_agreement_id',
                'sale_unit_name',
                'competition_name',
                'drop_in',
                'lead_source_name',
                'has_higher_sla',
                'is_multi_year',
                'has_additional_hardware',
                'has_service_credits',
                'remarks',
                'notes',
                'campaign_name',
            ];

            $dateAttributes = [
                'opportunity_start_date',
                'opportunity_end_date',
                'opportunity_closing_date',
                'expected_order_date',
                'customer_order_date',
                'purchase_order_date',
                'supplier_order_date',
                'supplier_order_transaction_date',
                'supplier_order_confirmation_date',
            ];

            $intAttributes = [
                'contract_duration_months',
                'ranking',
            ];

            $floatAttributes = [
                'opportunity_amount',
                'purchase_price',
                'list_price',
                'estimated_upsell_amount',
                'margin_value',
            ];

            $stringAttributes = [
                'personal_rating',
            ];

            $boolAttributes = [
                'is_opportunity_start_date_assumed',
                'is_opportunity_end_date_assumed',
                'is_contract_duration_checked',
                'has_higher_sla',
                'is_multi_year',
                'has_additional_hardware',
                'has_service_credits',
            ];

            foreach ($attributes as $attribute) {
                if ($this->has($attribute)) {
                    $value = $this->input($attribute);

                    if (in_array($attribute, $dateAttributes, true)) {
                        $value = transform($value, static fn (string $value) => Carbon::createFromFormat('Y-m-d', $value));
                    }

                    if (in_array($attribute, $intAttributes, true)) {
                        $value = transform($value, static fn (mixed $value) => (int) $value);
                    }

                    if (in_array($attribute, $floatAttributes, true)) {
                        $value = transform($value, static fn (mixed $value) => (float) $value);
                    }

                    if (in_array($attribute, $stringAttributes, true)) {
                        $value = transform($value, static fn (mixed $value) => (string) $value);
                    }

                    if (in_array($attribute, $boolAttributes, true)) {
                        $value = transform($value, static fn (mixed $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN));
                    }

                    $data[$attribute] = $value;
                }
            }

            return new UpdateOpportunityData($data);
        });
    }
}
