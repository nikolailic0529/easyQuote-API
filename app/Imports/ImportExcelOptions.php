<?php

namespace App\Imports;

final class ImportExcelOptions
{
    const REGEXP_PD = '/(pricing[\h]{1,4}document|reference[\h]{1,4}no\.):/i';

    const REGEXP_SH = '/(system[\h]{1,4}handle|system[\h]{1,4}id):/i';

    const REGEXP_SAID = '/(service[\h]{1,4}agreement(?:[\h]{1,4}id)?|service[\h]{1,4}id):/i';
}
