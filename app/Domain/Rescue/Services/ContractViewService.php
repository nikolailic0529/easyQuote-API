<?php

namespace App\Domain\Rescue\Services;

use App\Domain\DocumentMapping\Collections\MappedRows;
use App\Domain\Rescue\Concerns\FetchesGroupDescription;
use App\Domain\Rescue\Contracts\ContractView;
use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Queries\ContractQueries;
use App\Domain\Rescue\Resources\V1\QuoteResource;
use Illuminate\Database\Query\Builder;

class ContractViewService implements ContractView
{
    use FetchesGroupDescription;

    public static string $contractExportView = 'quotes.pdf';

    /** Template Fields which will be displayed only for S4 Service. */
    public static array $systemHiddenFields = ['service_level_description', 'pricing_document', 'system_handle'];

    /** Template Fields which will be hidden when Quote Mode is Contract. */
    public static array $contractHiddenFields = ['price', 'searchable'];

    public function setComputableRows(Contract $contract)
    {
        $rows = (new ContractQueries())
            ->mappedOrderedRowsQuery($contract)
            ->when(
                $contract->groupsReady(),
                fn (Builder $query) => $query->whereIn('id', $contract->groupedRows()),
                fn (Builder $query) => $query->where('is_selected', true)
            )
            ->get()
            ->map(static function (mixed $row) use ($contract): mixed {
                return data_set($row, 'country_iso_3166_2', $contract->customer?->country?->iso_3166_2);
            });

        $contract->computableRows = MappedRows::make($rows);

        return $this;
    }

    public function prepareContractReview(Contract $contract)
    {
        $this->setComputableRows($contract);

        $this->prepareRows($contract);

        $this->prepareSchedule($contract);

        return $this;
    }

    public function export(Contract $contract)
    {
        $export = $this->prepareContractExport($contract);

        $filename = $this->makePdfFilename($contract);

        return app('snappy.pdf.wrapper')
            ->loadView(static::$contractExportView, $export)
            ->download($filename);
    }

    public function prepareRows(Contract $contract)
    {
        $contract->computableRows = $contract->computableRows->exceptHeaders(static::$contractHiddenFields);
        $contract->renderableRows = $contract->computableRows->exceptHeaders(static::$systemHiddenFields);

        if ($contract->groupsReady()) {
            $this->formatGroupDescription($contract);

            return $this;
        }

        return $this;
    }

    public function prepareSchedule(Contract $contract)
    {
        if (!isset($contract->scheduleData->value)) {
            return $this;
        }

        $contract->scheduleData->value = MappedRows::make($contract->scheduleData->value);

        return $this;
    }

    protected function prepareContractExport(Contract $contract): array
    {
        $this->prepareContractReview($contract);

        $resource = QuoteResource::make($contract->enableReview())->resolve();
        $data = to_array_recursive(data_get($resource, 'quote_data', []));

        $assets = $this->getTemplateAssets($contract->contractTemplate);

        return compact('data') + $assets;
    }

    protected function getTemplateAssets($template)
    {
        $design = tap($template->form_data, function (&$design) {
            if (isset($design['payment_page'])) {
                $design['payment_schedule'] = $design['payment_page'];
                unset($design['payment_page']);
            }
        });

        $company_logos = $template->company->logoSelection ?? [];
        $vendor_logos = $template->vendor->logoSelection ?? [];
        $images = array_merge($company_logos, $vendor_logos);

        return compact('design', 'images');
    }

    protected function formatGroupDescription(Contract $contract)
    {
        /** @var \App\Domain\DocumentMapping\Collections\MappedRows $computableRows */
        $computableRows = $contract->computableRows = static::mapGroupDescriptionWithRows($contract, $contract->computableRows);

        $computableRows
            ->exceptHeaders(self::$contractHiddenFields)
            ->setHeadersCount()
            ->setCurrency($contract->currencySymbol);

        $renderRows = (clone $computableRows)->exceptHeaders(self::$systemHiddenFields);

        $contract->renderableRows = $renderRows->map->except('total_price');

        return $this;
    }

    protected function formatLinePrices(Contract $contract)
    {
        $contract->renderableRows = $contract->renderableRows->setCurrency($contract->currencySymbol);

        return $this;
    }

    private function makePdfFilename(Contract $contract): string
    {
        $hash = md5($contract->contract_number.time());

        return "{$contract->contract_number}_{$hash}.pdf";
    }
}
