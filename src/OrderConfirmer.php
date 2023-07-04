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
class OrderConfirmer
{
    /**
     * @var \Sequra
     */
    private $module;
    private $context;
    private $cart;
    private $customer;
    private $client;
    private $ipn;
    private $sq_state;
    private $amount_paid = 0;
    private $order;
    private $merchant_id;
    private $sequra_order_uri;
    private $sequra_order;

    public function __construct($module, $context_or_ipn)
    {
        $this->module = $module;
        if ('ipn' == $context_or_ipn) {
            $this->initFromIpn();
        } else {
            $this->initFromContext($context_or_ipn);
        }
    }

    public function initFromIpn()
    {
        $this->sq_state = \Tools::getValue('sq_state', null);
        $this->merchant_id = \Tools::getValue('merchant_id');
        $cart_id = \Tools::getValue('cart_id');
        if (SequraTools::sign($cart_id) != \Tools::getValue('signed')) {
            echo 'La firma del carrito no concuerda.';
            exit;
        }
        $this->context = \Context::getContext();
        $this->cart = new \Cart($cart_id);
        $this->context->cart = $this->cart;
        $this->context->customer = new \Customer($this->cart->id_customer);
        $address = new \Address($this->context->cart->id_address_delivery);
        $this->context->country = new \Country($address->id_country);
        $this->context->language = new \Language((int) $this->cart->id_lang);
        $this->context->currency = new \Currency((int) $this->cart->id_currency);
        // Not a full URI but the client lib will add the rest:
        $this->sequra_order_uri = \Tools::getValue('order_ref');
        $this->ipn = true;
        if ($this->sq_state == 'approved') {
            $this->amount_paid = $this->cart->getOrderTotal(true, \Cart::BOTH);
        }
    }

    public function initFromContext($context)
    {
        $this->context = $context;
        $this->cart = $context->cart;
        $this->sequra_order_uri = $context->cookie->sequra_order;
    }

    public function run()
    {
        $this->order = new \Order(
            SequraTools::getOrderIdByCartId($this->cart->id)
        );
        $this->module->currentOrder = !is_null($this->order) ? $this->order->id : null;
        $this->module->currentOrderReference = !is_null($this->order) ? $this->order->reference : null;
        switch ($this->sq_state) {
            case 'needs_review':
                $this->runSetOnHold();
                break;
            case 'confirmed-without-number':
                $this->runResendOderNumber();
                break;
            default: // approved set as default for backward compat
                $this->runConfirm();
        }
    }

    protected function runSetOnHold()
    {
        $this->abortIfOnHoldPreconditionsAreMissing();
        $this->validateWithSequra();
        if (!$this->ipn) {
            $this->redirectToOrderConfirmation();
        }
    }

    protected function runConfirm()
    {
        $this->abortIfConfirmationPreconditionsAreMissing();
        $this->validateWithSequra();
        if (!$this->ipn) {
            $this->redirectToOrderConfirmation();
        }
    }

    protected function runResendOderNumber()
    {
        $this->prepareSequra();
        if (!$this->approvedBySequra()) {
            return;
        }
        if (!$this->module->currentOrder) {
            $this->registerCartAsOrder();
        }
        $this->registerOrderForDelayedReporting();
        $this->sendOrderRefToSequra();
        $this->clearSequraEnvironment();
        exit;
    }

    protected function abortIfOnHoldPreconditionsAreMissing()
    {
        if (!$this->module->active ||
            $this->orderIsCreated() ||
            $this->cartIsAbandoned() ||
            $this->customerHasDisappeared()
        ) {
            $this->raise410Error('Raised from abortIfOnHoldPreconditionsAreMissing');
        }
    }

    protected function abortIfConfirmationPreconditionsAreMissing()
    {
        if (!$this->module->active ||
            $this->orderIsCompleted() ||
            $this->cartIsAbandoned() ||
            $this->customerHasDisappeared()
        ) {
            $this->raise410Error('Raised from abortIfConfirmationPreconditionsAreMissing');
        }
    }

    public function orderIsCompleted()
    {
        if ($this->orderIsCreated() && !$this->orderIsPending()) {
            return true;
        }

        return false;
    }

    private function orderIsCreated()
    {
        return (bool) SequraTools::getOrderIdByCartId($this->cart->id);
    }

    private function orderIsPending()
    {
        if ($this->order->getCurrentState() == \Configuration::get('SEQURA_OS_NEEDS_REVIEW') ||
            $this->isOutOfStockState($this->order)) {
            return true;
        }

        return false;
    }

    public function cartIsAbandoned()
    {
        $cart = $this->cart;

        return $cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0;
    }

    public function customerHasDisappeared()
    {
        $cart = $this->cart;
        $this->customer = new \Customer($cart->id_customer);

        return !\Validate::isLoadedObject($this->customer);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function validateWithSequra()
    {
        $this->prepareSequra();
        if (!$this->approvedBySequra()) {
            return;
        }
        if ($this->sq_state != 'needs_review' && $this->orderIsCreated()) {
            $this->updateCartsOrderState();
        } else {
            $this->registerCartAsOrder();
        }
        $this->sendOrderRefToSequra();
        if ($this->sq_state != 'needs_review') {
            $this->registerOrderForDelayedReporting();
            $this->clearSequraEnvironment();
        }
    }

    public function prepareSequra()
    {
        $this->client = $this->module->getClient();
        $builder = $this->module->getOrderBuilder($this->merchant_id);
        $this->sequra_order = $builder->build(
            $this->sq_state == 'needs_review' ? 'on_hold' : 'confirmed'
        );
    }

    public function approvedBySequra()
    {
        if ($this->orderIsCreated()) {
            return true;
        }
        $this->client->updateOrder($this->sequra_order_uri, $this->sequra_order);
        if ($this->client->succeeded()) {
            return true;
        }
        if ($this->client->cartHasChanged()) {
            if ($this->ipn) {
                $this->raise410Error('Raised from approvedBySequra due to cartHasChanged');
            } else {
                $this->redirectToCartChanged();
            }
        } else {
            exit($this->client->dump());
        }

        return false;
    }

    public function restart()
    {
        $linker = $this->context->link;
        if ($this->ipn) {
            $step2 = OrderConfirmer::ipnUrl();
        } else {
            $step2 = $linker->getModuleLink('sequra', 'confirmation', ['added_fee' => 1], true);
        }
        \Tools::redirect($step2);
    }

    public static function ipnUrl($cart_id = null)
    {
        $linker = \Context::getContext()->link;
        $params = [];
        if ($cart_id) {
            $params = array_merge($params, self::ipnParams($cart_id));
        }

        return $linker->getModuleLink('sequra', 'ipn', $params, true);
    }

    public static function ipnParams($cart_id)
    {
        $params = ['cart_id' => '' . $cart_id, 'signed' => SequraTools::sign($cart_id)];
        $params['id_shop'] = '' . \Context::getContext()->shop->id;
        $params['id_lang'] = '' . \Context::getContext()->language->id;

        return $params;
    }

    private function isOutOfStockState($order)
    {
        return in_array($order->getCurrentState(), [
            \Configuration::get('PS_OS_OUTOFSTOCK'),
            \Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
        ]);
    }

    private function getConfiguredState()
    {
        $state_name = 'SEQURA_OS_' . strtoupper($this->sq_state);

        return (int) \Configuration::get($state_name);
    }

    private function getValidOrderState()
    {
        $configured_state = $this->getConfiguredState();
        $order_status = new \OrderState(
            $configured_state,
            (int) $this->context->language->id
        );
        if (!\Validate::isLoadedObject($order_status)) {
            if ($this->sq_state != 'needs_review') {
                // FAll back to PS_OS_PAYMENT y SEQURA_OS_APPROVED isn't defined
                return (int) \Configuration::get('PS_OS_PAYMENT');
            } else { // Just in case State wasn't created plugin wasn't upgraded properly
                $installer = new Installer($this->module);
                $installer->install();
                $configured_state = $this->getConfiguredState();
            }
        }

        return $configured_state;
    }

    protected function updateCartsOrderState()
    {
        $this->order->addOrderPayment($this->amount_paid, null, $this->getTransactionId());
        foreach (\Order::getByReference($this->order->reference) as $order) {
            $outofstock = $this->isOutOfStockState($order);
            $new_history = new \OrderHistory();
            $new_history->id_order = (int) $order->id;
            $new_history->changeIdOrderState(
                $this->getConfiguredState(),
                (int) $order->id,
                true
            );
            $new_history->addWithemail();
            if ($outofstock) {
                $history = new \OrderHistory();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState(
                    (int) \Configuration::get('PS_OS_OUTOFSTOCK_PAID'),
                    $order->id,
                    true
                );
                $history->addWithemail();
            }
        }
    }

    protected function registerCartAsOrder()
    {
        $cart = $this->cart;
        $this->module->secret_handshake = true;
        try {
            $this->module->validateOrder(
                (int) $cart->id,
                $this->getValidOrderState(),
                $this->amount_paid,
                $this->module->getDisplayName(),
                null,
                ['transaction_id' => $this->getTransactionId()],
                (int) $this->context->currency->id,
                false,
                $this->context->customer->secure_key
            );
        } catch (\Exception $e) {
            $logger = new \PrestaShopLogger();
            $logger->addLog('Exception at order validation: ' . $e->getMessage());
            if (!$this->orderIsCompleted()) {
                $this->raise410Error('Raised from registerCartAsOrder due to: ' . $e->getMessage());
            }
            // If the order is completed despite the Exception lets continue
        }
        $this->module->secret_handshake = false;
    }

    public function registerOrderForDelayedReporting()
    {
        (new Reporter($this->module))->registerSequraOrder($this->module->currentOrder, $this->merchant_id);
    }

    public function sendOrderRefToSequra()
    {
        $order_ref = [
            'order_ref_1' => $this->module->currentOrder,
        ];
        if (\Configuration::get('SEQURA_ORDER_ID_FIELD') == 0) {
            $order_ref = [
                'order_ref_1' => $this->module->currentOrderReference,
                'order_ref_2' => $this->module->currentOrder,
            ];
        }
        $extra = ['merchant_reference' => $order_ref];
        $builder = $this->module->getOrderBuilder($this->merchant_id);
        $order = $builder->getPartialOder(
            array_merge($this->sequra_order, $extra)
        );
        $this->client->updateOrder($this->sequra_order_uri, $order, 'PATCH');
        if (!$this->client->succeeded()) {
            // TODO: find a way to log internally
        }
    }

    public function clearSequraEnvironment()
    {
        $this->context->cookie->sequra_order_invoice = '';
        $this->context->cookie->sequra_order_part = '';
    }

    public function redirectToOrderConfirmation()
    {
        \Tools::redirect(
            'index.php?controller=order-confirmation&'
            . 'id_cart=' . (int) $this->cart->id
            . '&id_module=' . (int) $this->module->id
            . '&id_order=' . $this->module->currentOrder
            . '&key=' . $this->customer->secure_key .
            (\Tools::getValue('sq_product') ? '&sq_product=' . \Tools::getValue('sq_product') : '')
        );
    }

    public function redirectToCartChanged()
    {
        $linker = \Context::getContext()->link;
        $abort_url = $linker->getPageLink(
            'order',
            true,
            null,
            'step=3&sequra_error=' . SEQURA_ERROR_CART_CHANGED
        );
        \Tools::redirect($abort_url);
    }

    public function raise410Error($msg = null)
    {
        if ($this->ipn) {
            http_response_code(410);
            exit($msg);
        } else {
            \Tools::redirect('index.php?controller=order&step=1'); // FIXME
        }
    }

    protected function getTransactionId()
    {
        return preg_replace('/.*\//', '', $this->sequra_order_uri);
    }
}
