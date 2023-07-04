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

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\Module\PrestashopSequra\Configuration\Settings;

class AdminAjaxSequraController extends ModuleAdminController
{
    /**
     * @var Sequra
     */
    public $module;

    /**
     * @var bool
     */
    public $ajax = true;

    /**
     * @var bool
     */
    protected $json = true;

    /**
     * AJAX: Check credentials
     */
    public function ajaxProcessHasValidCredentials()
    {
        $this->ajaxDie(
            json_encode(
                ['valid_credentials' => $this->module->getClient()->isValidAuth()]
            )
        );
    }

    /**
     * AJAX: Update Configurations Key
     */
    public function ajaxProcessUpdateConfigKey()
    {
        $key = self::safeGetKey();
        $value = Tools::getValue('value');
        if (strpos($key, 'SEQURA') !== 0) {
            http_response_code(400);
            exit('Invalid key');
        } else {
            if ($key == 'SEQURA_COUNTRIES') {
                $countries = array_filter(
                    explode(',', $value),
                    function ($country) {
                        return in_array($country, ['ES', 'IT', 'FR', 'PT']);
                    }
                );
                array_walk(
                    $countries,
                    [
                        '\PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings',
                        'updateActivePaymentMethods'
                    ]
                );
                $value = implode(',', $countries);
            }
            \Configuration::updateValue($key, $value);
            if ($key == 'SEQURA_COUNTRIES') {
                $this->module->setAllowedCountries($this->context->shop->id);
            }
            $this->ajaxDie(json_encode(['success' => true]));
        }
    }

    public function displayAjaxGetConfig()
    {
        $this->ajaxDie($this->module->getConfigJson());
    }

    public function displayAjaxGetConfigValue()
    {
        $key = self::safeGetKey();
        if ($key == 'SEQURA_PASS') {
            $this->ajaxDie('********');
        }

        $this->ajaxDie(\Configuration::get($key));
    }

    /**
     * AJAX: getPaymentMethods
     */
    public function ajaxProcessGetPaymentMethods()
    {
        $settings = new Settings($this->module);
        $this->ajaxDie(
            json_encode(
                $settings->getMethodsForAllCountries()
            )
        );
    }

    private static function safeGetKey()
    {
        $key = Tools::getValue('key');
        if (strpos($key, 'SEQURA_') !== 0) {
            http_response_code(400);
            exit('Invalid key');
        }

        return $key;
    }

    /**
     * AJAX: Change prestashop rounding settings
     *
     * PS_ROUND_TYPE need to be set to 1 (Round on each item)
     * PS_PRICE_ROUND_MODE need to be set to 2 (Round up away from zero, wh
     */
    public function ajaxProcessEditRoundingSettings()
    {
        \Configuration::updateValue('PS_ROUND_TYPE', '1');
        \Configuration::updateValue('PS_PRICE_ROUND_MODE', '2');
        $this->ajaxDie(json_encode(true));
    }
}
