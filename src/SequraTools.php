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

class SequraTools
{
    const ISO8601_PATTERN = '^((\d{4})-([0-1]\d)-([0-3]\d))+$|P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$';
    public static $centsPerWhole = 100;
    public static $thirdPartyOnePagers = [
        'onepagecheckout',
        'onepagecheckoutps',
        'esp_1stepcheckout',
        'threepagecheckout',
    ];

    public static function needsBasicPresentation()
    {
        foreach (self::$thirdPartyOnePagers as $name) {
            if (SequraTools::isModuleActive($name)) {
                return $name;
            }
        }

        return false;
    }

    public static function removeSequraOrderFromSession()
    {
        $cookie_array = \Context::getContext()->cookie->getAll();
        foreach ($cookie_array as $key => $value) {
            if (preg_match('/sequra(.*)_order/', $key)) {
                \Context::getContext()->cookie->__unset($key);
            }
        }
    }

    public static function getUname()
    {
        if (!function_exists('php_uname')) {
            return 'uname unavailable';
        }
        return php_uname();
    }

    public static function removeProtectedKeys(&$data, $keys)
    {
        foreach ($keys as $key) {
            unset($data[$key]);
        }
    }

    public static function removeUnnecessaryKeys(&$data, $necessaryKeys)
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $necessaryKeys)) {
                unset($data[$key]);
            }
        }
    }

    public static function makeIntegerPrices(&$data, $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::integerPrice($data[$key]);
            }
        }
    }

    public static function integerPrice($price)
    {
        return (int) round(self::$centsPerWhole * $price);
    }

    public static function notNull($value1)
    {
        return is_null($value1) ? '' : $value1;
    }

    public static function isInt($value1)
    {
        return (int) $value1 > 0;
    }

    public static function translateKeys(&$data, $keys, $object = null)
    {
        foreach ($keys as $api => $my) {
            if ($object) {
                unset($data[$my]);
                $data[$api] = '' . $object->{$my};
            } elseif ($api != $my && array_key_exists($my, $data)) {
                $data[$api] = is_null($data[$my]) ? '' : $data[$my];
                unset($data[$my]);
            }
        }
    }

    public static function truncateKeys(&$data, $keys)
    {
        foreach ($keys as $key => $length) {
            if ($length > 0 && isset($data[$key])) {
                $data[$key] = mb_substr($data[$key], 0, $length);
            } else {
                unset($data[$key]);
            }
        }
    }

    public static function dieObject($object)
    {
        return;
        /* echo '<xmp style="text-align: left;">';
        print_r($object);
        echo '</xmp><br />';
        exit('END');*/
    }

    public static function sign($value)
    {
        $signature = base64_encode(openssl_digest($value . \Configuration::get('SEQURA_PASS'), 'sha256', true));

        return $signature ? $signature : sha1($value . \Configuration::get('SEQURA_PASS'));
    }

    public static function getOrderStatus($orderstate, $name)
    {
        $ret = 'processing';
        if ('shipped' == strtolower($name)) {
            $ret = 'shipped';
        }
        if (($orderstate instanceof \OrderState) && $orderstate->shipped) {
            $ret = 'shipped';
        }
        if (false !== strpos(strtolower($name), 'cancel')) { // Just a guess
            $ret = 'cancelled';
        }

        return $ret;
    }

    public static function getPaymentMethod($module)
    {
        switch ($module) {
            case 'servired':
            case 'redsys':
            case 'cc':
            case 'iupay':
            case 'cecatpv':
            case 'ceca':
            case 'bbva':
            case 'paytpv':
            case 'rurlavia':
            case 'univia':
            case 'banesto':
            case 'stripe':
            case 'stripejs':
            case 'banc_sabadell':
            case 'InnovaCommerceTPV':
                return 'CC';
            case 'paypal':
            case 'paypalwithfee':
                return 'PP';
            case 'bankwire':
            case 'cheque':
                return 'TR';
            case 'cod':
            case 'codfee':
            case 'cashondelivery':
            case 'megareembolso':
            case 'seurcashondelivery':
                return 'COD';
            default:
                if (strpos($module, 'sequra') === 0) {
                    return 'SQ';
                }

                return 'O/' . $module;
        }
    }

    // @todo stuff below should be in a separate class

    public static function totals($cart)
    {
        $total_without_tax = $total_with_tax = 0;
        foreach ($cart['items'] as $item) {
            $total_without_tax += isset($item['total_without_tax']) ? $item['total_without_tax'] : 0;
            $total_with_tax += isset($item['total_with_tax']) ? $item['total_with_tax'] : 0;
        }

        return ['without_tax' => $total_without_tax, 'with_tax' => $total_with_tax];
    }

    public static function isModuleActive($name)
    {
        $module = \Module::getInstanceByName($name);
        if ($module && \Module::isInstalled($name)) {
            if (method_exists('Module', 'isEnabled')) {
                if (\Module::isEnabled($name)) {
                    return true;
                }
            } else {
                if ($module->active) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function curl_get_contents($url)
    {
        $ch = curl_init();
        $timeout = 5;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }

    public static function stripHTML($item)
    {
        array_walk(
            $item,
            function (&$value) {
                if (is_string($value)) {
                    $value = html_entity_decode(strip_tags($value));
                }
            }
        );

        return $item;
    }

    public static function getOrderIdByCartId($id_cart)
    {
        return \Order::getIdByCartId((int) $id_cart);
    }

    public static function getOrderByCartId($id_cart)
    {
        return \Order::getByCartId((int) $id_cart);
    }

    public static function getSession()
    {
        return \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()->get('session');
    }
}
