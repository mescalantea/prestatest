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

use PrestaShop\Module\PrestashopSequra\SequraTools;

class SequraReturnModuleFrontController extends ModuleFrontController
{
    public $ssl = true;


    /**
     * Initialize order confirmation controller.
     *
     * @see FrontController::init()
     */
    public function init()
    {
        sleep(10);
        parent::init();
        $redirectLink = 'index.php?controller=order';
        $id_cart = (int) Tools::getValue('id_cart', 0);
        $id_module = (int) Tools::getValue('id_module', 0);
        $id_order = SequraTools::getOrderIdByCartId($id_cart);
        $secure_key = \Tools::getValue('key', false);
        if (!$id_order || !$id_module || !$secure_key || empty($secure_key)) {
            // @todo: add error message
            \Tools::redirect($redirectLink . (Tools::isSubmit('slowvalidation') ? '&slowvalidation' : ''));
        }
        \Tools::redirect('index.php?' . http_build_query([
            'controller' => 'order-confirmation',
            'id_cart' => $id_cart,
            'id_module' => $id_module,
            'id_order' => $id_order,
            'key' => $secure_key,
            'sq_product' => \Tools::getValue('sq_product', 'i1'),
        ]));
    }
}
