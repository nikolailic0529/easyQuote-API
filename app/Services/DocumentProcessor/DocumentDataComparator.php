<?php

namespace App\Services\DocumentProcessor;

use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Services\DocumentProcessor\Exceptions\DocumentComparisonException;
use Illuminate\Support\Arr;

class DocumentDataComparator
{
    /**
     * Resolves the file which contains more data.
     *
     * @throws \App\Services\DocumentProcessor\Exceptions\DocumentComparisonException
     */
    public function __invoke(QuoteFile $aQuoteFile, QuoteFile $bQuoteFile): QuoteFile
    {
        if (false === $this->hasSameFileType($aQuoteFile, $bQuoteFile)) {
            throw DocumentComparisonException::differentFileTypes($aQuoteFile, $bQuoteFile);
        }

        $result = $this->performDocumentsComparison($aQuoteFile, $bQuoteFile);

        if ($result < 1) {
            return $aQuoteFile;
        }

        return $bQuoteFile;
    }

    protected function hasSameFileType(QuoteFile $aQuoteFile, QuoteFile $bQuoteFile): bool
    {
        return $aQuoteFile->file_type === $bQuoteFile->file_type;
    }

    /**
     * Compares the data from the two price list type documents.
     * Return integer from -1 to 1,
     * Where -1 means the data from the left document is more complete and truthful,
     *      0 means the data from the left & right documents are the same,
     *      1 means the data from the right document is more complete and truthful.
     *
     * @param \App\Models\QuoteFile\QuoteFile $aQuoteFile
     * @param \App\Models\QuoteFile\QuoteFile $bQuoteFile
     * @return int
     * @throws \App\Services\DocumentProcessor\Exceptions\DocumentComparisonException
     */
    protected function performDocumentsComparison(QuoteFile $aQuoteFile, QuoteFile $bQuoteFile): int
    {
        $aDocumentData = $this->getComparableData($aQuoteFile);
        $bDocumentData = $this->getComparableData($bQuoteFile);

        if ($this->isPriceListDocument($aQuoteFile)) {
            return $this->comparePriceListData($aDocumentData, $bDocumentData);
        }

        if ($this->isPaymentScheduleDocument($aQuoteFile)) {
            return $this->comparePaymentScheduleData($aDocumentData, $bDocumentData);
        }

        throw DocumentComparisonException::unsupportedFileType($aQuoteFile);
    }

    /**
     * @throws \App\Services\DocumentProcessor\Exceptions\DocumentComparisonException
     */
    protected function getComparableData(QuoteFile $quoteFile): array
    {
        if ($this->isPriceListDocument($quoteFile)) {
            return array_map(function (ImportedRow $importedRow) {
                return $importedRow->columns_data->all();
            }, $quoteFile->rowsData->all());
        }

        if ($this->isPaymentScheduleDocument($quoteFile)) {
            if (!is_null($quoteFile->scheduleData)) {
                return Arr::wrap($quoteFile->scheduleData->value);
            } else {
                return [];
            }
        }

        throw DocumentComparisonException::unsupportedFileType($quoteFile);
    }

    protected function isPriceListDocument(QuoteFile $quoteFile): bool
    {
        return in_array($quoteFile->file_type, [QFT_PL, QFT_WWPL], true);
    }

    protected function isPaymentScheduleDocument(QuoteFile $quoteFile): bool
    {
        return in_array($quoteFile->file_type, [QFT_PS], true);
    }

    /**
     * Compares the data from the two price list type documents.
     *
     * @param array $aDocumentData
     * @param array $bDocumentData
     * @return int
     */
    protected function comparePriceListData(array $aDocumentData, array $bDocumentData): int
    {
        // TODO: implement deep rows comparison.
        if (count($aDocumentData) === count($bDocumentData)) {
            return 0;
        }

        if (count($aDocumentData) > count($bDocumentData)) {
            return -1;
        }

        return 1;
    }

    /**
     * Compares the data from the two payment schedule type documents.
     *
     * @param array $aDocumentData
     * @param array $bDocumentData
     * @return int
     */
    protected function comparePaymentScheduleData(array $aDocumentData, array $bDocumentData): int
    {
        if (count($aDocumentData) === count($bDocumentData)) {
            return 0;
        }

        if (count($aDocumentData) > count($bDocumentData)) {
            return -1;
        }

        return 1;
    }
}
