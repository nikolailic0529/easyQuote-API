<?php

namespace App\Http\Requests\QuoteTemplate;

use App\Contracts\Services\HpeExporter;
use App\Models\QuoteTemplate\BaseQuoteTemplate;
use App\Models\QuoteTemplate\TemplateDesign;
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

    public function getDesign(): array
    {
        if (!($template = $this->route('hpe_contract_template')) instanceof BaseQuoteTemplate) {
            return [];
        }

        $images = $this->exporter->retrieveTemplateImages($template);

        return transform(
            TemplateDesign::getPages(TemplateDesign::HPE_CONTRACT),
            fn ($pages) => collect($pages)->map(fn ($page) => array_merge($page, $images))->toArray()
        );
    }
}
