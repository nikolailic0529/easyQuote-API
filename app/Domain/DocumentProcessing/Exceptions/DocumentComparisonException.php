<?php

namespace App\Domain\DocumentProcessing\Exceptions;

use App\Domain\QuoteFile\Models\QuoteFile;

class DocumentComparisonException extends \Exception
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
