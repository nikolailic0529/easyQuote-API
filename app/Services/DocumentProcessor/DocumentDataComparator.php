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
    public function __invoke(QuoteFile $aFile, QuoteFile $bFile): QuoteFile
    {
        if (false === $this->hasSameFileType($aFile, $bFile)) {
            throw DocumentComparisonException::differentFileTypes($aFile, $bFile);
        }

        $result = $this->performDocumentsComparison($aFile, $bFile);

        if ($result < 1) {
            return $aFile;
        }

        return $bFile;
    }

    protected function hasSameFileType(QuoteFile $aFile, QuoteFile $bFile): bool
    {
        return $aFile->file_type === $bFile->file_type;
    }

    /**
     * Compares the data from the two price list type documents.
     * Return integer from -1 to 1,
     * Where -1 means the data from the left document is more complete and truthful,
     *      0 means the data from the left & right documents are the same,
     *      1 means the data from the right document is more complete and truthful.
     *
     * @param \App\Models\QuoteFile\QuoteFile $aFile
     * @param \App\Models\QuoteFile\QuoteFile $bFile
     * @return int
     * @throws \App\Services\DocumentProcessor\Exceptions\DocumentComparisonException
     */
    protected function performDocumentsComparison(QuoteFile $aFile, QuoteFile $bFile): int
    {
        $aDocumentData = $this->getComparableData($aFile);
        $bDocumentData = $this->getComparableData($bFile);

        if ($this->isPriceListDocument($aFile)) {
            return $this->comparePriceListData($aDocumentData, $bDocumentData);
        }

        if ($this->isPaymentScheduleDocument($aFile)) {
            return $this->comparePaymentScheduleData($aDocumentData, $bDocumentData);
        }

        throw DocumentComparisonException::unsupportedFileType($aFile);
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
