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

use PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PreQualifier
{
    protected static $MODULE_NAME = 'sequra';
    protected static $SERVICE_COMPATIBLE = true;
    /**
     * @var \Cart
     */
    private $cart;
    /**
     * @var \Sequra|false
     */
    private $module;
    
    public function __construct($cart)
    {
        $this->cart = $cart;
        $this->module = \Module::getInstanceByName(static::$MODULE_NAME);
    }

    public function passes()
    {
        return
            static::available($this->module) &&
            $this->isCartElegible() &&
            $this->priceWithinRange() &&
            static::availableForIP();
    }

    public static function canDisplayWidgetInProductPage($id_product)
    {
        if (!self::availableForIP() || !$id_product) {
            return false;
        }
        $sq_product_extra = new ProductExtra($id_product);
        if ($sq_product_extra->getProductIsBanned()) {
            return false;
        }
        if (
            !\Configuration::get('SEQURA_FOR_SERVICES') &&
            $sq_product_extra->getProductIsVirtual()
        ) {
            return false;
        }

        return true;
    }

    public static function isPriceWithinMethodRange($method, $price, $check_min = true)
    {
        $max = $method['max_amount'] / 100;
        $min = $method['min_amount'] / 100;
        $too_much = is_numeric($max) && $max > 0 && $price > $max;
        $too_low = (is_numeric($min) && $min > 0 && $price < $min) && $check_min;

        return !$too_much && !$too_low;
    }

    public static function isPriceWithinRange($price, $check_min = true)
    {
        $ret = array_filter(
            PaymentMethodsSettings::getMerchantPaymentMethods(false, self::getCountryCode()),
            function ($method) use ($price, $check_min) {
                return
                    self::isPriceWithinMethodRange(
                        $method,
                        $price,
                        $check_min && PaymentMethodsSettings::getFamilyFor($method) != 'PARTPAYMENT'
                    );
            }
        );

        return count($ret) > 0;
    }

    public static function isDateInRange($method)
    {
        $to_date = isset($method['ends_at']) ? strtotime($method['ends_at']) : 0;
        $from_date = isset($method['starts_at']) ? strtotime($method['starts_at']) : 0;

        return (!$from_date || time() >= $from_date) &&
            (!$to_date || time() <= $to_date);
    }

    private static function getCountryCode()
    {
        $cart = \Context::getContext()->cart;
        if ($cart && $cart->id_address_delivery) {
            $address = new \Address($cart->id_address_delivery);

            return \Country::getIsoById($address->id_country);
        } elseif ($cart && $cart->id_address_delivery) {
            $address = new \Address($cart->id_address_delivery);

            return \Country::getIsoById($address->id_country);
        }

        return strtoupper(substr(\Context::getContext()->language->iso_code, -2));
    }

    public static function canShowBanner($key)
    {
        $show_banner = \ConfigurationCore::get($key, null, null, null, 0);

        return $show_banner && self::canDisplayInfo();
    }

    public static function canDisplayInfo($price = null)
    {
        // For this plugin widgets are added on page footer (footer.tpl)
        return false;
    }

    public static function available($module)
    {
        if ($module && \Module::isInstalled(static::$MODULE_NAME)) {
            $available = true;
            if (\Configuration::get('SEQURA_FOR_SERVICES')) {
                $available = static::$SERVICE_COMPATIBLE;
            }
            if (method_exists('Module', 'isEnabled')) {
                if (\Module::isEnabled(static::$MODULE_NAME)) {
                    return $available;
                }
            } else {
                if ($module->active) {
                    return $available;
                }
            }
        }

        return false;
    }

    public static function availableForIP()
    {
        $allowed_ips = preg_split('/[\s*,]/', \Configuration::get('SEQURA_ALLOW_IP'), null, PREG_SPLIT_NO_EMPTY);

        return empty($allowed_ips) || in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) || isset($_COOKIE['SEQURA_INTEGRATOR']);
    }

    public function priceWithinRange()
    {
        $price = $this->cart->getOrderTotal();

        return static::isPriceWithinRange($price);
    }

    public function isCartElegible()
    {
        $banned_products = array_filter(
            $this->cart->getProducts(),
            function ($cart_item) {
                $sq_product_extra = new ProductExtra($cart_item['id_product']);

                return $sq_product_extra->getProductIsBanned();
            }
        );

        return count($banned_products) == 0;
    }

    public function allowedCountry()
    {
        $address = new \Address((int) $this->cart->id_address_delivery);
        $country = new \Country((int) $address->id_country);

        return in_array($country->iso_code, $this->module->getCountries());
    }
}
