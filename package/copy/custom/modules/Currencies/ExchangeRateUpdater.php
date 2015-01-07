<?php

/*
 * This file is part of the Currencies Exchange Rate Updater, a SugarCRM
 * package designed to ease the process of updating active currencies exchange
 * rates with the help of external data sources.
 *
 * Copyright (c) 2012 João Morais and 
 *               2014 Kristofer Tingdahl (only minor changes)
 * http://github.com/tingdahl/currencies-exchange-rate-updater
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * @license MIT
 *   See LICENSE.txt shipped with this package.
 */

class ExchangeRateUpdater {

    /**
     * Retrieve system active currencies, except the one used by Sugar as
     * default.
     *
     * @return array of Currencies.
     */
    public static function getCurrencies()
    {
        $defaultIso4217 = SugarConfig::getInstance()->get('default_currency_iso4217');

        $bean = BeanFactory::getBean('Currencies');

        $currencies = $bean->get_full_list(
            sprintf("%s.name",
                $bean->table_name
            ),
            sprintf("%s.status LIKE 'Active' AND %s.iso4217 NOT LIKE '%s'",
                $bean->table_name,
                $bean->table_name,
                $defaultIso4217
            )
        );

        return $currencies;
    }

    /**
     * Retrieve latest exchange rates from ECB.
     * API given by: https://www.ecb.europa.eu/stats/exchange/eurofxref/html/index.en.html
     *
     * @param array $settings
     *    Not used. Kept to keep code compatible with base functionality.
     *
     * @return array of iso4217 values as keys and latest rates as values, who
     * match the iso4217 values of $currencies.
     */
    public static function getLatestRates( array $settings )
    {
        $latestRates = array();
        $defaultIso4217 = SugarConfig::getInstance()->get('default_currency_iso4217');
	if ( $defaultIso4217=='EUR' )
	{
	    $XMLContent =
		file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

	    foreach($XMLContent as $line)
	    {
		if(preg_match("/currency='([[:alpha:]]+)'/",$line,$iso4217))
		{
		    if(preg_match("/rate='([[:graph:]]+)'/",$line,$rate))
		    {
			$latestRates[$iso4217[1]] = $rate[1];
		    }
		}
	    }
	}

        return $latestRates;
    }

    /**
     * Save supplied rates.
     *
     * @param $rates array of iso4217 values as keys and latest rates as values.
     */
    public static function saveLatestRates(array $rates)
    {
        foreach ($rates as $currencyId => $rate) {
            $currency = BeanFactory::getBean('Currencies')->retrieve($currencyId);
            $currency->conversion_rate = $rate;
            $currency->save();
        }
    }

}
