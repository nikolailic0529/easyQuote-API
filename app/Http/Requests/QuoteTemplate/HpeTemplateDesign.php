<?php

namespace App\Http\Requests\QuoteTemplate;

use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Models\QuoteTemplate\BaseQuoteTemplate;
use App\Models\QuoteTemplate\TemplateDesign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class HpeTemplateDesign extends FormRequest
{
    protected Vendors $vendors;

    public function __construct(Vendors $vendors)
    {
        $this->vendors = $vendors;
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

        $hpe = $this->vendors->findByCode('HPE');

        $logos = array_merge(
            $template->company->logoDimensions ?? [],
            $hpe->logoDimensions ?? []
        );

        return transform(
            TemplateDesign::getPages(TemplateDesign::HPE_CONTRACT),
            fn ($pages) => collect($pages)->map(fn ($page) => array_merge($page, $logos))->toArray()
        );
    }
}
