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

class Identification
{
    public $ssl = true;
    /**
     * @var \Sequra
     */
    private $module;
    /**
     * @var \Sequra\PhpClient\Client
     */
    private $client = null;
    /**
     * @var \Context
     */
    private $context;

    public function __construct($module)
    {
        $this->module = $module;
        $this->client = $module->getClient();
        $this->context = \Context::getContext();
    }

    public function sequraIsReady($reuse = false)
    {
        if ($reuse && SequraSession::recoverUri()) {
            return true;
        }
        $builder = $this->module->getOrderBuilder();
        $this->client->startSolicitation($builder->build());
        SequraSession::storeUri($this->client->getOrderUri());
        return $this->client->succeeded();
    }

    public function displayForStandardPurchase($controller)
    {
        $identity_form = null;
        if ($this->sequraIsReady()) {
            $options = [
                'product' => $this->module->getProduct(),
                'ajax' => \Tools::getValue('ajax', false),
                'campaign' => $this->module->getCampaign(),
            ];
            $identity_form = $this->client->getIdentificationForm(
                SequraSession::recoverUri(),
                $options
            );
        }
        $name = $this->module->getConfigName();
        $this->context->smarty->assign([
            'service_name' => \Configuration::get($name . '_NAME'),
            'identity_form' => $identity_form,
        ]);
        $this->display($controller);
    }

    public function display($controller)
    {
        $this->abortIfUnavailableOrCookieMissing();

        $this->setVariables();
        $template = 'module:sequra/views/identification.tpl';
        $controller->setTemplate($template);
    }

    public function setVariables()
    {
        $this->context->smarty->assign($this->context->cart->getSummaryDetails());
        $this->module->setVariables($this->context->cart);
    }

    private function abortIfUnavailableOrCookieMissing()
    {
        if (!SequraSession::recoverUri()) {
            \Tools::redirect('index.php?controller=order');
        }
        $this->abortIfUnavailable();
    }

    private function abortIfUnavailable()
    {
        $qualifier = new PreQualifier($this->context->cart);
        if (!$qualifier->passes()) {
            \Tools::redirect('index.php?controller=order');
        }
    }
}
