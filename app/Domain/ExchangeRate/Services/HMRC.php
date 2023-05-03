<?php

namespace App\Domain\ExchangeRate\Services;

final class HMRC
{
    /**
     * Request Service Url.
     */
    const SERVICE_URL = 'http://www.hmrc.gov.uk/softwaredevelopers/rates/exrates-monthly-{m}{y}.xml';

    /**
     * Currency Code related attribute.
     */
    const XML_ATTR_CURRENCY_CODE = 'currencyCode';

    /**
     * Country Code related attribute.
     */
    const XML_ATTR_COUNTRY_CODE = 'countryCode';

    /**
     * Exchange Rate related attribute.
     */
    const XML_ATTR_EXCHANGE_RATE = 'rateNew';
}
