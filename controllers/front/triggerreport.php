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

use PrestaShop\Module\PrestashopSequra\Reporter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SequraTriggerreportModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        $this->submitDailyReport();
        if ('' == \Configuration::get('SEQURA_REPORT_ERROR')) {
            exit('ok');
        }
        http_response_code(599);
        exit('ko');
    }

    private function submitDailyReport()
    {
        if ($_SERVER['HTTP_USER_AGENT'] == 'sequra-cron') {
            ob_start();
            \Tools::redirect("/");
            echo ' ';
            // flush any buffers and send the headers
            while (@ob_end_flush()) {
            }
            flush();
        }

        // This would run in background if UA- sequra-cron
        return (new Reporter($this->module))->submitDailyReport();
    }
}
