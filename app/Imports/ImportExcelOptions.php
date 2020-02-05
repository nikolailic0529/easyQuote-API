<?php

namespace App\Imports;

final class ImportExcelOptions
{
    const REGEXP_PD = '/(pricing[\h]{1,4}document|reference[\h]{1,4}no\.):/i';

    const REGEXP_PD_VALUE = '/(?:pricing[\h]{1,4}document|reference[\h]{1,4}no\.):\h*(\w+)/i';

    const REGEXP_SH = '/(system[\h]{1,4}handle|system[\h]{1,4}id|support[\h]{1,4}account[\h]{1,4}reference):/i';

    const REGEXP_SH_VALUE = '/(?:system[\h]{1,4}handle|system[\h]{1,4}id|support[\h]{1,4}account[\h]{1,4}reference):\h*(\b[\w\- ]+\b)/i';

    const REGEXP_SAID = '/(service[\h]{1,4}agreement(?:[\h]{1,4}id)?|service[\h]{1,4}id):/i';

    const REGEXP_SAID_VALUE = '/(?:service[\h]{1,4}agreement(?:[\h]{1,4}id)?|service[\h]{1,4}id):\h*((?:[\d]{4}[\s]*){3,4})/i';
}
