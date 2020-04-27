<?php

namespace App\Services\PdfParser;

final class PdfOptions
{
    const REGEXP_PRICE_LINES_01 = '/^(?<product_no>\d+\-\w{3}|[a-zA-Z]\w{3,4}?[a-zA-Z]{1,2})\s+(?<description>(.(?![\s\h]{4,}))+[\w\-\+]+)\s+(?<serial_no>\d?[a-zA-Z]{1,3}[a-zA-Z\d]{7,8})\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+(\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,4}))(?=\s))([a-zA-Z].+)?$/m';

    const REGEXP_PRICE_LINES_02 = '/^(?<product_no>\d+\-\w{3}|[a-zA-Z]\w{3,4}?[a-zA-Z]{1,4})\s+(?<description>(.(?![\s\h]{4,}))+[\w\-\+]+)\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?\s+(?<price>(\d{1,3},)?\d+([,\.]\d{1,2}))$\s+(?<serial_no>\d?[a-zA-Z]{1,3}[a-zA-Z-\d]{7,12})$/m';

    const REGEXP_PRICE_LINES_03 = '/^(?<product_no>\d+\-\w{3}|[a-zA-Z]\w{3,4}?[a-zA-Z]{1,2})\s+(?:(.(?![\s\h]{4,}))+[\w\-\+]+)[\v\h]+\n+[\v\h]+(?<description>(.(?![\s\h]{4,}))+[\w\-\+]+)[\h]+(?<serial_no>\d?[a-zA-Z]{1,3}[a-zA-Z\d]{7,8})?[\v\h]*((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+(\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,4}))(?=\s))([a-zA-Z].+)?$/m';

    const REGEXP_PRICE_SAID = '/(?<=Service Agreement ID:)(.+)/i';

    const REGEXP_PD = '/(pricing[\h]{1,4}document|reference[\h]{1,4}no\.):\s+(\w+)\s/im';

    const REGEXP_SH = '/system[\h]{1,4}handle:\h{1,}((?!service|coverage|description|subtotal)\b[\w\- ]+\b)(?:\h{2,}|\n)/im';

    const REGEXP_SAID = '/service[\h]{1,4}agreement(?:[\h]{1,4}id)?:\s+((?:[\d]{4}[\s]+){3,4})\s/im';

    const SCHEDULE_MATCHES = ['payments', 'payment_dates', 'payment_dates_options'];

    const REGEXP_SCHEDULE_PAYMENTS = '/(?<payment_dates>((?:system (?:handle|id)|periode de|from|zeitraum\h+von)(\h*:)?)(?<date>(?:[\ha-z-]+)(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))([\.\/]\d{2,4})(?:[\ha-z-]*?))+$)|(^(\h)?(?!payment (\h+)? schedule)(?:period au|to)?(?<payment_dates_options>(\g\'date\')+(?:([\ha-z-]+)?)$))/mi';

    const REGEXP_SCHEDULE_PRICE = '/(\h{2,}((\p{Sc})?[ ]?(?<price>([\d]+[ ]?[,\']?)?[,\.]?\d+[,\.]?\d+)))/';

    const REGEXP_SCHEDULE_DATE = '/(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})/';

    const REGEXP_SCHEDULE_COLUMNS = '/(?:(?:periode\h+de|system\h+(?:handle|id)|from|zeitraum\h+von)(?:\h*:)?)|(?<cols>(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})|(\b(\w+)\b))/mi';

    const CACHE_PREFIX_RAW_DATA = 'raw-data:';

    const SYSTEM_HEADER_ONE_PAY = '_one_pay';

    const REGEXP_ONE_PAY = '/return to/i';
}
