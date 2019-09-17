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
            'quote_id' => 'required|exists:quotes,id',
            'quote_file_id' => 'required|exists:quote_files,id',
            'data_select_separator_id' => $this->requiredIfCsv(),
            'page' => 'integer'
        ];
    }

    public function requiredIfCsv()
    {
        $id = $this->quote_file_id;

        if(!$this->quoteFile->exists($id)) {
            return '';
        };

        $quoteFile = $this->quoteFile->find($id);

        if(is_null($quoteFile) || !$quoteFile->isCsv()) {
            return '';
        }

        $extension = $quoteFile->format->extension;

        return $extension === 'csv' ? 'required|exists:data_select_separators,id' : '';
    }
}
