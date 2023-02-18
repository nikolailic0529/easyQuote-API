<?php

namespace App\Domain\Template\Requests\HpeContractTemplate;

use App\Domain\HpeContract\Contracts\HpeExporter;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Template\Models\TemplateForm;
use Illuminate\Foundation\Http\FormRequest;

class HpeTemplateDesignRequest extends FormRequest
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
        ];
    }

    public function getTemplateSchema(): array
    {
        if (!($template = $this->route('hpe_contract_template')) instanceof HpeContractTemplate) {
            return [];
        }

        $images = $this->exporter->retrieveTemplateImages($template);

        return transform(
            TemplateForm::getPages(TemplateForm::HPE_CONTRACT),
            fn ($pages) => collect($pages)->map(fn ($page) => array_merge($page, $images))->toArray()
        );
    }
}
