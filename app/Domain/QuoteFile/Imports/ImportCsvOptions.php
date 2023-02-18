<?php

namespace App\Domain\QuoteFile\Imports;

final class ImportCsvOptions
{
    const REGEXP_PD = '/(pricing[\h]{1,4}document|document[\h]{1,4}id|reference[\h]{1,4}no\.)/i';

    const REGEXP_SH = '/(system[\h]{1,4}handle|system[\h]{1,4}id|support[\h]{1,4}account[\h]{1,4}reference)/i';

    const REGEXP_SAID = '/(service[\h]{1,4}agreement(?:[\h]{1,4}id)?|service[\h]{1,4}id)/i';
}
