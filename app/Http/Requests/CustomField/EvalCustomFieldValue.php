<?php

namespace App\Http\Requests\CustomField;

use App\Models\System\CustomField;
use App\Rules\ScalarValue;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class EvalCustomFieldValue extends FormRequest
{
    public function authorize(): Response
    {
        /** @var CustomField|null $field */
        $field = $this->route('custom_field');

        if (null === $field) {
            return Response::deny();
        }

        if (null === $field->calc_formula) {
            return Response::deny("The custom field `$field->field_name` doesn't support calculation.");
        }

        return Response::allow();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'variables' => ['bail', 'nullable', 'array'],
            'variables.*' => ['bail', 'nullable', new ScalarValue()],
        ];
    }

    public function getExpressionVariables(): array
    {
        return $this->input('variables', []);
    }
}
