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

class OrderUpdater
{
    /**
     * @var \Order
     */
    private $order;
    /**
     * @var \PrestaShopCollection
     */
    private $suborders;
    /**
     * @var array
     */
    private $suborder_ids;
    /**
     * @var \Sequra\PhpClient\Client
     */
    private $client;
    /**
     * @var \Sequra
     */
    private $module;
    /**
     * @var array
     */
    private $sequra_order;

    public function __construct($module, $order_id)
    {
        $this->module = $module;
        $this->order = new \Order($order_id);
        $this->suborder_ids = [];
        $this->suborders = \Order::getByReference($this->order->reference);
        foreach ($this->suborders as $order) {
            $this->suborder_ids[] = (int) $order->id;
        }
        $this->module = \Sequra::getInstance();
        $this->client = $this->module->getClient();
    }

    /**
     * Call this method to get singleton
     *
     * @return OrderUpdater
     */
    public static function getInstance($module, $order_id = null)
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new OrderUpdater($module, $order_id);
        }

        return $inst;
    }

    public function orderHasBeenInformedToSequra()
    {
        $sql = 'SELECT `order_id` FROM `' . _DB_PREFIX_ . 'sequra_order`' .
               'WHERE `order_id` = ' . (int) $this->order->id . ' ' .
               'AND `sent_to_sequra` = 1';
        \Db::getInstance()->execute($sql);

        return \Db::getInstance()->numRows() > 0;
    }

    public function someSubOrderHasBeenInformedToSequra()
    {
        $sql = 'SELECT `order_id` FROM `' . _DB_PREFIX_ . 'sequra_order`' .
               'WHERE `order_id` in (' . implode(',', $this->suborder_ids) . ') ' .
               'AND `sent_to_sequra` = 1';
        \Db::getInstance()->execute($sql);

        return \Db::getInstance()->numRows() > 0;
    }

    public function orderUpdateIfNeeded()
    {
        if (!$this->checkIfPreconditionsAreMet()) {
            return;
        }
        if ($this->updateWithSequra()) {
            Reporter::addMessageToOrder(
                [$this->order->id]
            );
        }
    }

    public function checkIfPreconditionsAreMet()
    {
        if ($this->wasPaidWithSequra() &&
            !$this->orderHasBeenInformedToSequra() &&
            $this->someSubOrderHasBeenInformedToSequra()
        ) {
            return true;
        }

        return false;
    }

    protected function wasPaidWithSequra()
    {
        $sql = 'SELECT order_id FROM ' . _DB_PREFIX_ . 'sequra_order WHERE `order_id` in (' . implode(
            ',',
            $this->suborder_ids
        ) . ') ';
        \Db::getInstance()->execute($sql);

        return \Db::getInstance()->numRows() > 0;
    }

    protected function getMerchantIdUsed()
    {
        $sql = 'SELECT merchant_id FROM ' . _DB_PREFIX_ . 'sequra_order WHERE order_id = ' . (int) $this->order->id . ' LIMIT 1';

        return \Db::getInstance()->getValue($sql);
    }

    public function updateWithSequra()
    {
        $this->prepareSequra();

        $this->client->orderUpdate($this->sequra_order);
        if ($this->client->succeeded()) {
            return true;
        } else {
            // @todo add some comment to the order or log
        }

        return false;
    }

    public function prepareSequra()
    {
        $builder = new ReportBuilder($this->getMerchantIdUsed(), [$this->order], []);
        $this->sequra_order = $builder->buildSingleOrder($this->order);
    }

    protected function emptyCart()
    {
        unset($this->sequra_order['cart']);
        $this->sequra_order['shipped_cart'] =
        $this->sequra_order['unshipped_cart'] = [
            'items' => [],
            'order_total_without_tax' => 0,
            'order_total_with_tax' => 0,
            'currency' => 'EUR',
        ];
    }
}
