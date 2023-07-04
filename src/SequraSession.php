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

class SequraSession
{
    private static $uri_name = 'sequra_order';

    public static function storeUri($uri)
    {
        self::getSession()->__set(self::$uri_name, $uri);
    }

    public static function unsetUri()
    {
        self::getSession()->__unset(self::$uri_name);
    }

    public static function recoverUri()
    {
        return self::getSession()->__get(self::$uri_name);
    }

    private static function getSession()
    {
        return \Context::getContext()->cookie;
    }
}
