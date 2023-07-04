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

namespace PrestaShop\Module\PrestashopSequra\Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\Module\PrestashopSequra\Crontab;

define('_SEQURA_SERVERS_IPS', '34.253.159.179,34.252.147.155,52.211.243.177');

class Settings
{
    protected $countries;
    protected $crontab;
    protected $context;
    protected $methods;
    protected $module;

    public static $MINIWIDGET_TYPES = ['CATEGORIES', 'CART', 'MINICART'];

    public function __construct($module)
    {
        $this->module = $module;
        $this->crontab = new Crontab();
        $this->context = $this->module->getContext();
        $this->countries = $this->module->getCountries();
        $this->getMethodsForAllCountries();
    }

    public function getConfigKeys()
    {
        return array_unique(
            array_merge(
                $this->getMerchantConfigKeys(),
                $this->getWidgetConfigKeys(),
                $this->getStatsConfigKeys()
            )
        );
    }

    public function getMerchantConfigKeys()
    {
        $ret = [
            'SEQURA_USER',
            'SEQURA_ALLOW_IP',
            'SEQURA_ORDER_ID_FIELD',
            'SEQURA_AUTOCRON',
            'SEQURA_AUTOCRON_H',
            'SEQURA_AUTOCRON_M',
            'SEQURA_ASSETS_KEY',
            'SEQURA_MODE',
            'SEQURA_FOR_SERVICES',
            'SEQURA_ALLOW_PAYMENT_DELAY',
            'SEQURA_ALLOW_REGISTRATION_ITEMS',
            'SEQURA_FOR_SERVICES_END_DATE',
            'SEQURA_SEND_CANCELLATIONS',
            'SEQURA_PS_CANCELED',
            'SEQURA_BANNED_CAT_IDS',
            'SEQURA_OS_APPROVED',
            'SEQURA_OS_NEEDS_REVIEW',
            'SEQURA_OS_CANCELED',
            'SEQURA_OS_APPROVED_LOWRISK',
            'SEQURA_OS_APPROVED_UNKNOWNRISK',
            'SEQURA_OS_APPROVED_HIGHRISK',
            'SEQURA_COUNTRIES',
        ];

        return array_merge(
            $ret,
            array_map(
                function ($code) {
                    return 'SEQURA_MERCHANT_ID_' . $code;
                },
                $this->module->getCountries()
            )
        );
    }

    public function getStatsConfigKeys()
    {
        return [
            'SEQURA_STATS_ALLOW',
            'SEQURA_STATS_AMOUNT',
            'SEQURA_STATS_PAYMENTMETHOD',
            'SEQURA_STATS_COUNTRIES',
            'SEQURA_STATS_BROWSER',
            'SEQURA_STATS_STATUS',
        ];
    }

    protected function getMethodCountryWidgetConfigKeys($method)
    {
        $country = $method['country'];
        $i18nproduct = PaymentMethodsSettings::buildUniqueI18ProductCode($method);
        $ret = [];
        if (PaymentMethodsSettings::getFamilyFor($method) != 'CARD') {
            $ret = [
                'SEQURA_' . $i18nproduct . '_SHOW_BANNER',
                'SEQURA_' . $i18nproduct . '_CSS_SEL',
                'SEQURA_' . $i18nproduct . '_WIDGET_THEME',
            ];
        }
        if (PaymentMethodsSettings::getFamilyFor($method) == 'PARTPAYMENT') {
            $ret = array_merge(
                $ret,
                [
                    'SEQURA_' . $country . '_PARTPAYMENT_PRODUCT',
                    'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_SHOW',
                    'SEQURA_' . $country . '_PARTPAYMENT_CART_SHOW',
                    'SEQURA_' . $country . '_PARTPAYMENT_MINICART_SHOW',
                    'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_TEASER_MSG',
                    'SEQURA_' . $country . '_PARTPAYMENT_CART_TEASER_MSG',
                    'SEQURA_' . $country . '_PARTPAYMENT_MINICART_TEASER_MSG',
                    'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_BELOW_MSG',
                    'SEQURA_' . $country . '_PARTPAYMENT_CART_BELOW_MSG',
                    'SEQURA_' . $country . '_PARTPAYMENT_MINICART_BELOW_MSG',
                    'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_CSS_SEL',
                    'SEQURA_' . $country . '_PARTPAYMENT_CART_CSS_SEL',
                    'SEQURA_' . $country . '_PARTPAYMENT_MINICART_CSS_SEL',
                    'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_CSS_SEL_PRICE',
                    'SEQURA_' . $country . '_PARTPAYMENT_CART_CSS_SEL_PRICE',
                    'SEQURA_' . $country . '_PARTPAYMENT_MINICART_CSS_SEL_PRICE',
                ]
            );
        }

        return $ret;
    }

    public function getCountryWidgetConfigKeys($country)
    {
        if (!$this->methods[$country]) {
            return [];
        }

        return array_merge(
            ...array_map(
                [$this, 'getMethodCountryWidgetConfigKeys'],
                $this->methods[$country]
            )
        );
    }

    public function getWidgetConfigKeys()
    {
        $ret = [
            'SEQURA_CSS_SEL_PRICE',
            'SEQURA_FORCE_NEW_PAGE',
        ];

        return array_merge(
            $ret,
            ...array_map(
                [$this, 'getCountryWidgetConfigKeys'],
                $this->module->getCountries()
            )
        );
    }

    public function getMethodsForAllCountries()
    {
        if (is_null($this->methods)) {
            $methods = [];
            foreach ($this->countries as $country) {
                $methods[$country] = PaymentMethodsSettings::getMerchantPaymentMethods(true, $country);
                array_walk(
                    $methods[$country],
                    function (&$method) use ($country) {
                        $method['country'] = $country;
                    }
                );
            }
            $this->methods = $methods;
        }

        return $this->methods;
    }

    public function getFileContents($file)
    {
        if (file_exists($file)) {
            return \Tools::file_get_contents($file);
        }

        return '';
    }

    public function getCustomCssPath($force = false)
    {
        $file_path_in_tpl = _PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/css/' . \Sequra::CSS_FILE;
        if ($force || file_exists($file_path_in_tpl)) {
            if (!file_exists(dirname($file_path_in_tpl))) {
                mkdir(dirname($file_path_in_tpl), 0755, true);
            }

            return $file_path_in_tpl;
        }

        return _PS_MODULE_DIR_ . '/' . $this->module->name . '/views/css/' . \Sequra::CSS_FILE;
    }

    public function getPaymentFormTplPath($force = false)
    {
        $tpl = (\Sequra::needsBasicPresentation() ? 'opc_' : '') . 'payment_info.tpl';
        $file_path_in_tpl = _PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/front/' . $tpl;
        if ($force || file_exists($file_path_in_tpl)) {
            if (!file_exists(dirname($file_path_in_tpl))) {
                mkdir(dirname($file_path_in_tpl), 0755, true);
            }

            return $file_path_in_tpl;
        }

        return _PS_MODULE_DIR_ . $this->module->name . '/views/templates/front/' . $tpl;
    }
}
