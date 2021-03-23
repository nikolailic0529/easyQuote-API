<?php

namespace App\Services;

use App\DTO\ContractTemplate\CreateContractTemplateData;
use App\DTO\ContractTemplate\UpdateContractTemplateData;
use App\Models\Template\ContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Services\Exceptions\ValidationException;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContractTemplateService
{
    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected ValidatorInterface $validator;

    public function __construct(ConnectionInterface $connection, LockProvider $lockProvider, ValidatorInterface $validator)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
    }

    public function createContractTemplate(CreateContractTemplateData $data): ContractTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new ContractTemplate(), function (ContractTemplate $contractTemplate) use ($data) {
            $contractTemplate->name = $data->name;
            $contractTemplate->business_division_id = $data->business_division_id;
            $contractTemplate->contract_type_id = $data->contract_type_id;
            $contractTemplate->company_id = $data->company_id;
            $contractTemplate->vendor_id = $data->vendor_id;
            $contractTemplate->currency_id = $data->currency_id;
            $contractTemplate->form_data = $data->form_data;

            $this->connection->transaction(function () use ($data, $contractTemplate) {
                $contractTemplate->save();

                $contractTemplate->countries()->sync($data->countries);
            });
        });
    }

    public function updateContractTemplate(ContractTemplate $contractTemplate, UpdateContractTemplateData $data): ContractTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($contractTemplate, function (ContractTemplate $contractTemplate) use ($data) {
            $contractTemplate->name = $data->name;
            $contractTemplate->business_division_id = $data->business_division_id;
            $contractTemplate->contract_type_id = $data->contract_type_id;
            $contractTemplate->company_id = $data->company_id;
            $contractTemplate->vendor_id = $data->vendor_id;
            $contractTemplate->currency_id = $data->currency_id;
            $contractTemplate->form_data = $data->form_data;

            $contractTemplate->data_headers = $data->data_headers;

            $this->connection->transaction(function () use ($data, $contractTemplate) {
                $contractTemplate->save();

                $contractTemplate->countries()->sync($data->countries);
            });

            if ($data->complete_design) {
                activity()
                    ->on($contractTemplate)
                    ->queue('updated');
            }
        });
    }

    public function deleteQuoteTemplate(ContractTemplate $contractTemplate): void
    {
        $this->connection->transaction(fn() => $contractTemplate->delete());
    }

    public function activateQuoteTemplate(ContractTemplate $contractTemplate): void
    {
        $contractTemplate->activated_at = now();

        $this->connection->transaction(fn() => $contractTemplate->save());
    }

    public function deactivateQuoteTemplate(ContractTemplate $contractTemplate): void
    {
        $contractTemplate->activated_at = null;

        $this->connection->transaction(fn() => $contractTemplate->save());
    }

    public function replicateQuoteTemplate(ContractTemplate $quoteTemplate): QuoteTemplate
    {
        activity()->disableLogging();

        $replicatedTemplate = $quoteTemplate->replicate(['user', 'countries', 'templateFields', 'is_active']);
        $countries = $quoteTemplate->countries()->pluck('id')->all();

        $this->connection->transaction(function () use ($countries, $replicatedTemplate) {

            $replicatedTemplate->save();
            $replicatedTemplate->countries()->sync($countries);

        });

        return tap($replicatedTemplate, function (ContractTemplate $replicatedTemplate) use ($quoteTemplate) {
            activity()->enableLogging();

            activity()
                ->on($replicatedTemplate)
                ->withProperties(['old' => ContractTemplate::logChanges($quoteTemplate), 'attributes' => ContractTemplate::logChanges($replicatedTemplate)])
                ->by(request()->user())
                ->queue('copied');
        });
    }
}
