<?php

namespace App\Services\PdfParser;

final class PdfOptions
{
    const REGEXP_PRICE_LINES_01 = '/^\h*(?<product_no>(?!software|hardware|Total|UNITED)\d+\-\w{3}|(?!software|hardware|Total|UNITED)\w[\w\-]{3,20}\w)\h+(?<description>(?:HPE\h+)?[\w\h\/\-\+\(\)\.]+\w)\h{2,}(?<serial_no>\w{4,30})\h+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})(?=\h*(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\h*)?(?<qty>\d{1,3}(?=\h{2,}))?(?:\h+(\p{Sc})?\h?(?<price>(?!\d{2}\.\d{2}\.\d{4})(?:\d{1,5}[,\.])?\d+(?:[,\.]\d{1,5})))(?:\h+\w.+)?$/m';

    const REGEXP_PRICE_LINES_02 = '/^\h*(?<product_no>(?!hardware|Total|UNITED)\d+\-\w{3}|(?!Total|UNITED)[a-zA-Z]\w{3,4}?[a-zA-Z]{1,4})\s+(?<description>(.(?![\s\h]{4,}))+[\w\-\+]+)\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?\s+(?<price>(\d{1,3},)?\d+([,\.]\d{1,2}))$\s+(?<serial_no>(?!software)\d?[a-zA-Z]{1,3}[a-zA-Z-\d]{7,12})$/mi';

    const REGEXP_PRICE_LINES_03 = '/^\h*(?<product_no>(?!hardware|Total|UNITED)\d+\-\w{3}|(?!hardware|Total|UNITED)[a-zA-Z]\w{3,4}?[a-zA-Z]{1,2})\s+(?:(.(?![\s\h]{4,}))+[\w\-\+]+)\h+\n+\h+(?<description>(.(?![\s\h]{4,}))+[\w\-\+]+)[\h]+(?<serial_no>\d?[a-zA-Z]{1,3}[a-zA-Z\d]{7,8})?[\v\h]*((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+(\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,4}))(?=\s))([a-zA-Z].+)?$/m';

    const REGEXP_PRICE_LINES_04 = '/^\h*(?<product_no>(?!hardware|Total|UNITED)\d+\-\w{3}|(?!hardware|Total|UNITED)[a-zA-Z]\w{3,4}?[a-zA-Z]{1,2})\s+(?<description>(.(?![\s\h]{4,}))+(?:Return to|RTS).*?[\w\-\+]+)\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+(\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,4})(,\d+)?)(?=\s))(.+)?$/mi';

    // product_no  description          qty  price
    //                         serial_no (?)
    const REGEXP_PRICE_LINES_05 = '/^\h*(?<product_no>(?!software|hardware|Total|UNITED|Contract)\d+\-\w{3}|(?!software|hardware|Total|UNITED|Contract)\w[\w\-]{3,20}\w)\h+(?<description>(?:HPE)?(?:[\h]{1,3}[\w\.\/\-\+\(\)]*[\h]{1,3}[\w\.\/\-\+\(\)]+)+)\h{2,}\h+(?<qty>\d{1,3}(?=\h{2,}))?(?:\h+(\p{Sc})?\h?(?<price>(?!\d{2}\.\d{2}\.\d{4})(?:\d{1,5}[,\.])?\d+(?:[,\.]\d{1,5})))(?:\h+\w.+)?$(?:\n+\h+(?<serial_no>[\w-]{4,30}))?$/mi';

    // product_no, description, date_from, date_to, qty
    const REGEXP_PRICE_LINES_06 = '/^\h*(?<product_no>(?!software|hardware|Total|UNITED)\d+\-\w{3}|(?!software|hardware|Total|UNITED)\w[\w\-]{3,20}\w)\h+(?<description>(?:HPE\h+)?[\w\h\/\-\+\(\)]+\w)\h{2,}(?<serial_no>\w{4,30})\h+(?<date_from>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{4})(?=\h+(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{4})\h+(?<date_to>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{4})\h+(?<qty>\d{1,3})$/m';

    // support lines
    const REGEXP_PRICE_LINES_SP = '/^\h*(?!HPE\h{2,})(?<product_no>(?!hardware|Total|UNITED)[\w\-]+)\h{2,15}(?<description>(\b[\/\w-]+\b ?)+)\n+\h+(?:\2\h+(?<date_to>\d{2}\.\d{2}\.\d{4})\h+(?<qty>\d+)\h+(?<price>\d+\.\d+))?/im';

    const REGEXP_PRICE_LINES_NS = '/^\h*(?<product_no>(?!software|hardware|Total|UNITED)\d+\-\w{3}|(?!software|hardware|Total|UNITED)[a-zA-Z]\w{3,12}?[a-zA-Z]{0,2})\s+(?<description>(HPE\h+|.(?![\s\h]{4,}))+[\w\-\+]+)\s{2,}((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))(\s+(\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,4})(,\d+)?)(?=\s))(.+)?$/m';

    const REGEXP_PRICE_LINES_GAPS = '/^(?<left_part>(?!.*(\g\'date_from\').*$).+?)\h{2,}(?<serial_no>\d?[a-zA-Z\d]{7,11})\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?(?=(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4}))?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+(\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,4})))([a-zA-Z].+)?$/m';

    const REGEXP_PRICE_SAID = '/(?<=Service Agreement ID:)(.+)/i';

    const REGEXP_PD = '/(pricing[\h]{1,4}document|reference[\h]{1,4}no\.):\s+(\w+)\s/im';

    const REGEXP_SH = '/system[\h]{1,4}handle:\h{1,}((?!service|coverage|description|subtotal)\b[\w\- ]+\b)(?:\h{2,}|\n)/im';

    const REGEXP_SAID = '/service[\h]{1,4}agreement(?:[\h]{1,4}id)?:\s+((?:[\d]{4}[\s]+){3,4})\s/im';

    const PRICE_COLS = ['product_no', 'description', 'serial_no', 'date_from', 'date_to', 'qty', 'price', 'searchable'];

    const SCHEDULE_MATCHES = ['payments', 'payment_dates', 'payment_dates_options'];

    const REGEXP_SCHEDULE_PAYMENTS = '/\h*(?<payment_dates>((?:system (?:handle|id)|periode de|from|zeitraum\h+von|Identifikacn.+)(\h*:)?)(?<date>(?:[\ha-z-]+)(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))([\.\/]\d{2,4})(?:[\ha-z-]*?))+$)|(^(\h)?(?!payment (\h+)? schedule)(?:period au|to)?(?<payment_dates_options>((?:[\ha-z-]+)(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))([\.\/]\d{2,4})(?:[\ha-z-]*?))+(?:([\ha-z-]+)?)$))/mi';

    const REGEXP_SCHEDULE_PRICE = '/(\h{2,}((\p{Sc})?[ ]?(?<price>([\d]+[ ]?[,\']?)?[,\.]?\d+[,\.]?\d+)))/';

    const REGEXP_SCHEDULE_DATE = '/(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})/';

    const REGEXP_SCHEDULE_COLUMNS = '/(?:(?:periode\h+de|system\h+(?:handle|id)|from|zeitraum\h+von|Identifikacn\hcslosystmu)(?:\h*:)?)|(?<cols>(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})|(\b(\w+)\b))/mi';

    const CACHE_PREFIX_RAW_DATA = 'raw-data:';

    const SYSTEM_HEADER_ONE_PAY = '_one_pay';

    const REGEXP_ONE_PAY = '/\b(RTS|return to)\b/i';
}
