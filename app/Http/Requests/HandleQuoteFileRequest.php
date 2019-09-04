<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;

class HandleQuoteFileRequest extends FormRequest
{
    protected $quoteFile;

    public function __construct(QuoteFileRepositoryInterface $quoteFile)
    {
        $this->quoteFile = $quoteFile;
    }

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
            'quote_file_id' => 'required|exists:quote_files,id',
            'data_select_separator_id' => [
                $this->requiredIfCsv(),
                'exists:data_select_separators,id'
            ],
            'page' => 'integer'
        ];
    }

    public function requiredIfCsv()
    {
        $id = $this->quote_file_id;

        $quoteFile = $this->quoteFile->get($id);

        $extension = $quoteFile->format->extension;

        if($extension === 'csv') {
            return 'required';
        }
    }
}
