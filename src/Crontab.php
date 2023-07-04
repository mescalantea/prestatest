<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    SeQura Tech <dev+prestashop@sequra.es>
 * @copyright Since 2013 SeQura WorldWide SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PrestashopSequra;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Crontab
{
    public static function calcNextExecutionTime()
    {
        return strtotime('tomorrow') + 3600 * \Configuration::get('SEQURA_AUTOCRON_H') + 60 * \Configuration::get('SEQURA_AUTOCRON_M');
    }

    public static function poolCron()
    {
        if (\Configuration::get('SEQURA_AUTOCRON') &&
            strpos($_SERVER['REQUEST_URI'], 'triggerreport') === false &&
            self::isTimeToSend()
        ) {
            // Avoid retry for 5 min at least.
            \Configuration::updateGlobalValue('SEQURA_AUTOCRON_NEXT', strtotime('now') + 300);

            $url = self::getTriggerReportUrl();
            $client = new \Sequra\PhpClient\Client();
            $client->callCron($url);
        }
    }

    protected static function isTimeToSend()
    {
        $next_time = (int) \Configuration::getGlobalValue('SEQURA_AUTOCRON_NEXT');
        // Test if nextime is not corrupted
        if ($next_time < strtotime('2 days ago')) {
            \Configuration::deleteByName('SEQURA_AUTOCRON_NEXT');
            \Configuration::updateGlobalValue('SEQURA_AUTOCRON_NEXT', strtotime('now') + 300);

            return false;
        }

        return strtotime('now') > $next_time;
    }

    public static function getTriggerReportUrl()
    {
        return \Context::getContext()->link->getModuleLink('sequra', 'triggerreport');
    }
}
