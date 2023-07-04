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

class PaymentMethodsSettings
{
    private static $RawMerchantPaymentMethods = [];
    private static $MerchantPaymentMethods = [];
    private static $productFamilyKeys = [
        'pp10' => 'CARD',       // Paga Ahora
        'fp1' => 'CARD',
        'i1' => 'INVOICE',     // Paga después
        'pp5' => 'INVOICE',
        'pp3' => 'PARTPAYMENT', // Paga fraccionado
        'pp6' => 'PARTPAYMENT',
        'pp9' => 'PARTPAYMENT',
        'sp1' => 'PARTPAYMENT',
    ];

    public static function buildUniqueProductCode($method)
    {
        return $method['product'] .
            (isset($method['campaign']) ? '_' . $method['campaign'] : '');
    }

    public static function buildUniqueI18ProductCode($method)
    {
        return self::buildUniqueProductCode($method) .
            (isset($method['country']) ? '_' . $method['country'] : '');
    }

    /**
     * Get method title from unique product code
     *
     * @param string $product_campaign unique product code
     *
     * @return string
     */
    public static function getTitleFromUniqueProductCode($product_campaign, $order_ref = null)
    {
        $product = $product_campaign;
        $campaign = '';
        if (count(explode('_', $product_campaign)) > 1) {
            list($product, $campaign) = explode('_', $product_campaign);
        }

        return self::getTitleFromProductCampaign($product, $campaign, $order_ref);
    }

    /**
     *  Get method title from unique product and campaign
     *
     * @param string $product product
     * @param string $campaign campaign
     *
     * @return string
     */
    public static function getTitleFromProductCampaign($product, $campaign = null, $order_ref = null)
    {
        $payment_methods = [];
        if ($order_ref) {
            $payment_methods = self::getPaymentMethods($order_ref);
        } else {
            $country_code = \Sequra::inferCountry();
            $payment_methods = self::getMerchantPaymentMethods(false, $country_code);
        }
        foreach ($payment_methods as $method) {
            if (
                $method['product'] == $product &&
                (!$campaign || !isset($method['campaign']) || $method['campaign'] == $campaign)
            ) {
                return $method['title'];
            }
        }

        return 'SeQura';
    }

    public static function getFamilyFor($method)
    {
        return self::$productFamilyKeys[$method['product']];
    }

    public static function updateActivePaymentMethods($country_code = 'ES')
    {
        $country_code = strtoupper($country_code);
        self::getMerchantPaymentMethods(true, $country_code); // Download methods again.
        $sq_products = self::getMerchantActivePaymentProducts($country_code);
        \Configuration::updateValue(
            'SEQURA_ACTIVE_METHODS_' . $country_code,
            serialize($sq_products)
        );
        if (in_array('i1', $sq_products)) {
            \Configuration::updateValue('SEQURA_' . $country_code . '_INVOICE_PRODUCT', 'i1');
        }
        if (in_array('pp5', $sq_products)) {
            \Configuration::updateValue('SEQURA_' . $country_code . '_CAMPAIGN_PRODUCT', 'pp5');
        }
        if (!in_array(\Configuration::get('SEQURA_' . $country_code . '_PARTPAYMENT_PRODUCT'), $sq_products)) {
            foreach (self::$productFamilyKeys as $product => $family) {
                if ($family !== 'PARTPAYMENT') {
                    continue;
                }
                if (in_array($product, $sq_products)) {
                    \Configuration::updateValue('SEQURA_' . $country_code . '_PARTPAYMENT_PRODUCT', $product);
                    break;
                }
            }
        }
        \Configuration::updateValue(
            'SEQURA_PARTPAYMENT_PRODUCT_MAX_AMOUNT_' . $country_code,
            self::getMaxAmountForPartPayment($country_code)
        );
    }

    public static function getMaxAmountForPartPayment($country_code = 'ES')
    {
        $method = array_filter(
            self::getMerchantPaymentMethods(false, $country_code),
            function ($method) use ($country_code) {
                return $method['product'] == \Configuration::get('SEQURA_' . $country_code . '_PARTPAYMENT_PRODUCT');
            }
        );
        $method = array_values($method);
        if (count($method) < 1 || !isset($method[0]['max_amount'])) {
            return -1;
        }

        return (int) $method[0]['max_amount'];
    }

    public static function getMerchantActivePaymentProducts($country_code = 'ES')
    {
        return array_map(
            function ($method) {
                return $method['product'];
            },
            self::getMerchantPaymentMethods(false, $country_code)
        );
    }

    public static function getMerchantPaymentMethods($force_refresh = false, $country_code = 'ES')
    {
        $merchant_id = \Configuration::get('SEQURA_MERCHANT_ID_' . $country_code);
        if (!$merchant_id) {
            return [];
        }
        if ($force_refresh || !self::getStoredPaymentMethods($country_code)) {
            $client = \Sequra::getInstance()->getClient();
            $client->getMerchantPaymentMethods($merchant_id);
            if ($client->succeeded()) {
                self::$RawMerchantPaymentMethods[$country_code] = $client->getRawResult();
                self::updateStoredPaymentMethods($country_code);
                $json = $client->getJson();
                self::$MerchantPaymentMethods[$country_code] = $json['payment_options'];
            }
        }
        if (!isset(self::$MerchantPaymentMethods[$country_code]) || !self::$MerchantPaymentMethods[$country_code]) {
            $json = self::getStoredPaymentMethods($country_code);
            self::$MerchantPaymentMethods[$country_code] = isset($json['payment_options']) ? $json['payment_options'] : [];
        }

        return self::flattenPaymentOptions(
            self::$MerchantPaymentMethods[$country_code]
        );
    }

    public static function getPaymentMethods($order_ref)
    {
        $client = \Sequra::getInstance()->getClient();
        $client->getPaymentMethods($order_ref);
        $json = $client->getJson();

        return self::flattenPaymentOptions(
            $json['payment_options']
        );
    }

    /**
     * Create a flat array with all methods in all options.
     *
     * @param array $options payment options to faltten
     *
     * @return array
     */
    private static function flattenPaymentOptions($options)
    {
        return $options ?
            array_reduce(
                $options,
                function ($methods, $family) {
                    $family['methods'] = array_map(
                        function ($method) {
                            $method['family'] = self::getFamilyFor($method);

                            return $method;
                        },
                        $family['methods']
                    );

                    return array_merge(
                        $methods,
                        $family['methods']
                    );
                },
                []
            )
            : [];
    }

    private static function getStoredPaymentMethods($country_code)
    {
        if (!isset(self::$RawMerchantPaymentMethods[$country_code]) || !self::$RawMerchantPaymentMethods[$country_code]) {
            self::$RawMerchantPaymentMethods[$country_code] = \Configuration::get('SEQURA_PAYMENT_METHODS_' . $country_code);
            if (mb_strlen(self::$RawMerchantPaymentMethods[$country_code], '8bit') < 4096 && file_exists(self::$RawMerchantPaymentMethods[$country_code])) {
                self::$RawMerchantPaymentMethods[$country_code] = \Tools::file_get_contents(self::$RawMerchantPaymentMethods[$country_code]);
            }
        }

        return json_decode(self::$RawMerchantPaymentMethods[$country_code], true);
    }

    private static function updateStoredPaymentMethods($country_code)
    {
        if (mb_strlen(self::$RawMerchantPaymentMethods[$country_code], '8bit') > 64000) {
            $tmp_file = tempnam(sys_get_temp_dir(), 'sq_pms_' . $country_code);
            file_put_contents($tmp_file, self::$RawMerchantPaymentMethods);
            self::$RawMerchantPaymentMethods[$country_code] = $tmp_file;
        }
        \Configuration::updateValue(
            'SEQURA_PAYMENT_METHODS_' . $country_code,
            self::$RawMerchantPaymentMethods[$country_code]
        );
    }
}
