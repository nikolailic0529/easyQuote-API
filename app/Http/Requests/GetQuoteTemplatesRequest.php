<?php namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class GetQuoteTemplatesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'company_id' => 'required|exists:companies,id',
            'vendor_id' => 'required|exists:vendors,id',
            'country_id' => 'required|exists:countries,id'
        ];
    }

    public function company(): Company
    {
        return app('company.repository')->find($this->company_id);
    }
}
