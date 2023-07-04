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
use PrestaShop\Module\PrestashopSequra\PSOrderUpdater;

class SequraWebhookModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        $event = \Tools::getValue('event', 'cancel');
        switch ($event) {
            case 'cancelled':
                $this->cancelledOrderFromWebhook();
                break;
            case 'risk_assessment':
                $this->setRiskLevelToOrder();
                break;
            default:
                $this->cancelOrderFromWebhook();
        }
        exit('Message received!');
    }

    private function cancelOrderFromWebhook()
    {
        $updater = PSOrderUpdater::getInstance("webhook");
        $updater->processCancellationRequest();
    }

    private function cancelledOrderFromWebhook()
    {
        $updater = PSOrderUpdater::getInstance("webhook");
        $updater->cancelOrder();
    }

    private function setRiskLevelToOrder()
    {
        $updater = PSOrderUpdater::getInstance("webhook");
        $updater->setRiskLevelToOrder();
    }
}
