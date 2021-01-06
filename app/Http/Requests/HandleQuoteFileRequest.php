<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Quote\Quote;
use App\Models\QuoteFile\QuoteFile;

class HandleQuoteFileRequest extends FormRequest
{
    protected ?Quote $quote = null;

    protected ?QuoteFile $quoteFile = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_id' => 'bail|required|exists:quotes,id',
            'quote_file_id' => 'bail|required|exists:quote_files,id',
            'data_select_separator_id' => value(function () {
                if (!$this->getQuoteFile()->isCsv()) {
                    return '';
                }

                return 'required|exists:data_select_separators,id';
            }),
            'page' => 'integer|min:1'
        ];
    }

    public function getQuote(): Quote
    {
        if (isset($this->quote)) {
            return $this->quote;
        }

        return Quote::findOrFail($this->input('quote_id'));
    }

    public function getQuoteFile(): QuoteFile
    {
        if (isset($this->quoteFile)) {
            return $this->quoteFile;
        }

        return QuoteFile::findOrFail($this->input('quote_file_id'));
    }
}
