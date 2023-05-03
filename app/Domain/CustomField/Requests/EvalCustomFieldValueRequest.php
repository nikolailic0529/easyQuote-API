<?php

namespace App\Domain\CustomField\Requests;

use App\Foundation\Validation\Rules\ScalarValue;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class EvalCustomFieldValueRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var \App\Domain\CustomField\Models\CustomField|null $field */
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
