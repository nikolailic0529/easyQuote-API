<?php

namespace App\Services\SalesOrderTemplate;

use App\DTO\{SalesOrderTemplate\CreateSalesOrderTemplateData,
    SalesOrderTemplate\TemplateDataHeader,
    SalesOrderTemplate\UpdateSalesOrderTemplateData,
    SalesOrderTemplate\UpdateSchemaOfSalesOrderTemplateData};
use App\Models\Template\SalesOrderTemplate;
use App\Models\Template\TemplateSchema;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class SalesOrderTemplateEntityService
{
    protected ConnectionInterface $connection;

    protected ValidatorInterface $validator;

    public function __construct(ConnectionInterface $connection, ValidatorInterface $validator)
    {
        $this->connection = $connection;
        $this->validator = $validator;
    }

    public function createSalesOrderTemplate(CreateSalesOrderTemplateData $data): SalesOrderTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap(new SalesOrderTemplate(), function (SalesOrderTemplate $salesOrderTemplate) use ($data) {

            $templateSchema = $this->initializeTemplateSchema();

            $salesOrderTemplate->templateSchema()->associate($templateSchema);
            $salesOrderTemplate->user()->associate($data->user_id);
            $salesOrderTemplate->businessDivision()->associate($data->business_division_id);
            $salesOrderTemplate->contractType()->associate($data->contract_type_id);
            $salesOrderTemplate->company()->associate($data->company_id);
            $salesOrderTemplate->vendor()->associate($data->vendor_id);
            $salesOrderTemplate->currency()->associate($data->currency_id);
            $salesOrderTemplate->name = $data->name;

            $this->connection->transaction(function () use ($data, $templateSchema, $salesOrderTemplate) {
                $templateSchema->save();
                $salesOrderTemplate->save();

                $salesOrderTemplate->countries()->sync($data->country_ids);
            });

        });
    }

    protected function initializeTemplateSchema(): TemplateSchema
    {
        return tap(new TemplateSchema(), function (TemplateSchema $templateSchema) {
            $templateSchema->{$templateSchema->getKeyName()} = (string)Uuid::generate(4);

            $templateSchema->form_data = [];
            $templateSchema->data_headers = value(function (): array {
                $headers = [];

                foreach (__('template.sales_order_data_headers') as $key => $header) {
                    $headers[$key] = $header['value'];
                }

                return $headers;
            });
        });
    }

    public function replicateSalesOrderTemplate(SalesOrderTemplate $salesOrderTemplate, User $actingUser = null): SalesOrderTemplate
    {
        return tap($salesOrderTemplate->replicate(['is_active']), function (SalesOrderTemplate $replicatedSalesOrderTemplate) use ($actingUser, $salesOrderTemplate) {
            $replicatedTemplateSchema = tap($salesOrderTemplate->templateSchema->replicate(), function (TemplateSchema $templateSchema) {
               $templateSchema->{$templateSchema->getKeyName()} = (string)Uuid::generate(4);
            });

            $replicatedSalesOrderTemplate->user()->associate($actingUser);
            $replicatedSalesOrderTemplate->templateSchema()->associate($replicatedTemplateSchema);
            $replicatedSalesOrderTemplate->name = "$replicatedSalesOrderTemplate->name [copy]";
            $replicatedSalesOrderTemplate->is_system = false;

            $countryKeys = $salesOrderTemplate->countries()->pluck($salesOrderTemplate->countries()->getQualifiedRelatedKeyName());

            $this->connection->transaction(function () use ($countryKeys, $replicatedTemplateSchema, $replicatedSalesOrderTemplate) {
                $replicatedTemplateSchema->save();
                $replicatedSalesOrderTemplate->save();

                $replicatedSalesOrderTemplate->countries()->sync($countryKeys);
            });
        });
    }

    public function updateSalesOrderTemplate(UpdateSalesOrderTemplateData $data, SalesOrderTemplate $salesOrderTemplate): SalesOrderTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap($salesOrderTemplate, function (SalesOrderTemplate $salesOrderTemplate) use ($data) {

            $salesOrderTemplate->user()->associate($data->user_id);
            $salesOrderTemplate->businessDivision()->associate($data->business_division_id);
            $salesOrderTemplate->contractType()->associate($data->contract_type_id);
            $salesOrderTemplate->company()->associate($data->company_id);
            $salesOrderTemplate->vendor()->associate($data->vendor_id);
            $salesOrderTemplate->currency()->associate($data->currency_id);
            $salesOrderTemplate->name = $data->name;

            $this->connection->transaction(function () use ($data, $salesOrderTemplate) {
                $salesOrderTemplate->save();

                $salesOrderTemplate->countries()->sync($data->country_ids);
            });

        });
    }

    public function updateSchemaOfSalesOrderTemplate(UpdateSchemaOfSalesOrderTemplateData $data, SalesOrderTemplate $salesOrderTemplate): SalesOrderTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        return tap($salesOrderTemplate, function (SalesOrderTemplate $salesOrderTemplate) use ($data) {

            $templateSchema = $salesOrderTemplate->templateSchema;

            $templateSchema->form_data = $data->form_data;

            $templateSchema->data_headers = array_merge( $templateSchema->data_headers, static::templateDataHeadersToDictionary(...$data->data_headers));

            $this->connection->transaction(function () use ($templateSchema) {
                $templateSchema->save();
            });

        });
    }

    private static function templateDataHeadersToDictionary(TemplateDataHeader ...$dataHeaders): array
    {
        $dictionary = [];

        foreach ($dataHeaders as $header) {
            $dictionary[$header->key] = $header->value;
        }

        return $dictionary;
    }

    public function markAsActiveSalesOrderTemplate(SalesOrderTemplate $salesOrderTemplate): void
    {
        $salesOrderTemplate->activated_at = $salesOrderTemplate->freshTimestampString();

        $this->connection->transaction(function () use ($salesOrderTemplate) {
            $salesOrderTemplate->save();
        });
    }

    public function markAsInactiveSalesOrderTemplate(SalesOrderTemplate $salesOrderTemplate): void
    {
        $salesOrderTemplate->activated_at = null;

        $this->connection->transaction(function () use ($salesOrderTemplate) {
            $salesOrderTemplate->save();
        });
    }

    public function deleteSalesOrderTemplate(SalesOrderTemplate $salesOrderTemplate): void
    {
        $this->connection->transaction(function () use ($salesOrderTemplate) {
            $salesOrderTemplate->delete();
        });
    }
}
