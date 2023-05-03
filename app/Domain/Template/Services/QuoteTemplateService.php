<?php

namespace App\Domain\Template\Services;

use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\DataTransferObjects\CreateQuoteTemplateData;
use App\Domain\Template\DataTransferObjects\UpdateQuoteTemplateData;
use App\Foundation\Validation\Exceptions\ValidationException;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuoteTemplateService
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

    public function createQuoteTemplate(CreateQuoteTemplateData $data): QuoteTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new QuoteTemplate(), function (QuoteTemplate $quoteTemplate) use ($data) {
            $quoteTemplate->name = $data->name;
            $quoteTemplate->business_division_id = $data->business_division_id;
            $quoteTemplate->contract_type_id = $data->contract_type_id;
            $quoteTemplate->company_id = $data->company_id;
            $quoteTemplate->currency_id = $data->currency_id;
            $quoteTemplate->form_data = $data->form_data;

            $this->connection->transaction(function () use ($data, $quoteTemplate) {
                $quoteTemplate->save();

                $quoteTemplate->vendors()->sync($data->vendors);
                $quoteTemplate->countries()->sync($data->countries);
            });
        });
    }

    public function updateQuoteTemplate(QuoteTemplate $quoteTemplate, UpdateQuoteTemplateData $data): QuoteTemplate
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($quoteTemplate, function (QuoteTemplate $quoteTemplate) use ($data) {
            $quoteTemplate->name = $data->name;
            $quoteTemplate->business_division_id = $data->business_division_id;
            $quoteTemplate->contract_type_id = $data->contract_type_id;
            $quoteTemplate->company_id = $data->company_id;
            $quoteTemplate->currency_id = $data->currency_id;
            $quoteTemplate->form_data = $data->form_data;

            $quoteTemplate->data_headers = $data->data_headers;

            $this->connection->transaction(function () use ($data, $quoteTemplate) {
                $quoteTemplate->save();

                $quoteTemplate->vendors()->sync($data->vendors);
                $quoteTemplate->countries()->sync($data->countries);
            });

            if ($data->complete_design) {
                activity()
                    ->on($quoteTemplate)
                    ->queue('updated');
            }
        });
    }

    public function deleteQuoteTemplate(QuoteTemplate $quoteTemplate): void
    {
        $this->connection->transaction(fn () => $quoteTemplate->delete());
    }

    public function activateQuoteTemplate(QuoteTemplate $quoteTemplate): void
    {
        $quoteTemplate->activated_at = now();

        $this->connection->transaction(fn () => $quoteTemplate->save());
    }

    public function deactivateQuoteTemplate(QuoteTemplate $quoteTemplate): void
    {
        $quoteTemplate->activated_at = null;

        $this->connection->transaction(fn () => $quoteTemplate->save());
    }

    public function replicateQuoteTemplate(QuoteTemplate $quoteTemplate): QuoteTemplate
    {
        activity()->disableLogging();

        $replicatedTemplate = $quoteTemplate->replicate(['user', 'countries', 'templateFields', 'is_active']);
        $countries = $quoteTemplate->countries()->pluck('id')->all();
        $vendors = $quoteTemplate->vendors()->pluck('id')->all();

        $this->connection->transaction(function () use ($vendors, $countries, $replicatedTemplate) {
            $replicatedTemplate->save();
            $replicatedTemplate->countries()->sync($countries);
            $replicatedTemplate->vendors()->sync($vendors);
        });

        return tap($replicatedTemplate, function (QuoteTemplate $replicatedTemplate) use ($quoteTemplate) {
            activity()->enableLogging();

            activity()
                ->on($replicatedTemplate)
                ->withProperties(['old' => QuoteTemplate::logChanges($quoteTemplate), 'attributes' => QuoteTemplate::logChanges($replicatedTemplate)])
                ->by(request()->user())
                ->queue('copied');
        });
    }
}
