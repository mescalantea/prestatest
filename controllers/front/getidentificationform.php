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
use PrestaShop\Module\PrestashopSequra\Identification;
use PrestaShop\Module\PrestashopSequra\SequraSession;

class SequraGetIdentificationFormModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function displayAjax()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        $sequra = \Sequra::getInstance();
        $options = [
            'product' => $sequra->getProduct(),
            'campaign' => $sequra->getCampaign(),
            'ajax' => true,
        ];
        echo $this->getIdentificationForm($options);
    }

    private function getIdentificationForm($options)
    {
        $name = 'sequra_order';
        $sequra = \Sequra::getInstance();
        $identification = new Identification($sequra);
        $retry = true;
        while ($identification->sequraIsReady()) {
            $client = $sequra->getClient();
            $result = $client->getIdentificationForm(
                SequraSession::recoverUri(),
                $options
            );
            if ($client->getStatus() == 200) {
                $this->context->cart->save();
                return $result;
            }
            if (!$retry) {
                SequraSession::unsetUri();
                http_response_code($client->getStatus());
                exit;
            }
            $retry = false;
        }
    }
}
