<?php

namespace App\Http\Requests\QuoteTemplate;

use App\Models\QuoteTemplate\BaseQuoteTemplate;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\RelationUsage;
use Illuminate\Support\Str;

class DeleteTemplate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\QuoteTemplate\BaseQuoteTemplate */
        $template = head($this->route()->parameters());

        if (!$template instanceof BaseQuoteTemplate) {
            return true;
        }

        $usage = RelationUsage::templateUsage($template);

        $relationName = Str::plural(static::detectRelationName($template), $usage);

        validator(
            [
                'usage_count' => $usage
            ],
            [
                'usage_count' => 'exclude_if:is_system,true|integer|size:0',
            ],
            [
                'usage_count.*' => sprintf('You could not delete the Template as %s %s %s it.', $usage, $relationName, Str::plural('use', $usage !== 1))
            ]
        )->validate();

        return true;
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

    protected static function detectRelationName(BaseQuoteTemplate $template)
    {
        return (string) Str::of(class_basename($template))
            ->before('Template')
            ->replaceMatches('/([a-z]*)([A-Z]*?)([A-Z][a-z]+)/', '$1 $2$3')
            ->trim();
    }
}
