<?php

namespace App\Http\Requests\HpeContractTemplate;

use App\Contracts\Services\HpeExporter;
use App\Models\Template\HpeContractTemplate;
use App\Models\Template\TemplateSchema;
use Illuminate\Foundation\Http\FormRequest;

class HpeTemplateDesign extends FormRequest
{
    protected HpeExporter $exporter;

    public function __construct(HpeExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function getTemplateSchema(): array
    {
        if (!($template = $this->route('hpe_contract_template')) instanceof HpeContractTemplate) {
            return [];
        }

        $images = $this->exporter->retrieveTemplateImages($template);

        return transform(
            TemplateSchema::getPages(TemplateSchema::HPE_CONTRACT),
            fn ($pages) => collect($pages)->map(fn ($page) => array_merge($page, $images))->toArray()
        );
    }
}
