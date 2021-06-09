<?php

namespace App\Services\DocumentProcessor\Exceptions;

use App\Models\QuoteFile\QuoteFile;
use Exception;

class DocumentComparisonException extends Exception
{
    public static function differentFileTypes(QuoteFile $aQuoteFile, QuoteFile $bQuoteFile): self
    {
        return new static(sprintf("The given documents {%s}, {%s} have different types, '%s' != '%s'.", $aQuoteFile->getKey(), $bQuoteFile->getKey(), $aQuoteFile->file_type, $bQuoteFile->file_type));
    }

    public static function unsupportedFileType(QuoteFile $quoteFile)
    {
        return new static(sprintf("The given document {%s} has unsupported file type '%s'.", $quoteFile->getKey(), $quoteFile->file_type ?? 'NULL'));
    }
}
