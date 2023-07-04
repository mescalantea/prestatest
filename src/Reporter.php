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

class Reporter
{
    const SEQURA_DR_SENT_TXT = 'Se ha informado a SeQura sobre el envío realizado.';
    /**
     * Module
     *
     * @var \Sequra
     */
    protected $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }

    public function registerSequraOrder($order_id, $merchant_id)
    {
        $sql = 'INSERT IGNORE INTO ' . _DB_PREFIX_ .
            'sequra_order (`order_id`, `sent_to_sequra`, `merchant_id`) VALUES ' .
            '(' . $order_id . ', 0, "' . $merchant_id . '")';
        \Db::getInstance()->execute($sql);
    }

    public function submitDailyReport()
    {
        \Configuration::updateValue('SEQURA_REPORT_ERROR', 'Not sent yet');
        $reports = array_map(
            [$this, 'submitDailyReportForMerchant'],
            $this->merchantIdsToReport()
        );

        return $reports;
    }

    public function submitDailyReportForMerchant($merchant_id)
    {
        if (!$merchant_id) {
            $merchant_id = \Configuration::get('SEQURA_MERCHANT_ID');
        }
        $orders = $this->ordersToReport($this->orderIdsToReport($merchant_id));
        $stats = $this->statsToReport(7);
        $builder = $this->getReportBuilder($merchant_id, $orders, $stats);
        $report = $builder->build();
        $client = $this->module->getClient();
        $client->sendDeliveryReport($report);
        if ($client->succeeded()) {
            $this->dequeueOrders($builder->getReportOrderIds());
            self::addMessageToOrder($builder->getReportOrderIds());
            \Configuration::updateValue('SEQURA_REPORT_ERROR', '');
        } else {
            \Configuration::updateValue('SEQURA_REPORT_ERROR', 'Faulty report: ' . print_r($client->getJson(), true));
        }
        \Configuration::updateGlobalValue('SEQURA_AUTOCRON_NEXT', Crontab::calcNextExecutionTime());

        return $report;
    }

    public function ordersToReport($order_ids)
    {
        $objects = [];
        $id_shop = (int) \Context::getContext()->shop->id;
        foreach ($order_ids as $id) {
            $object = new \Order((int) $id);
            if ($object->id && $this->orderOrRelatedOrderHasBeenShipped($object)) {
                if ($id_shop == $object->id_shop) {
                    $objects[] = $object;
                }
            }
        }

        return $objects;
    }

    private function orderOrRelatedOrderHasBeenShipped($primary_order)
    {
        $orders = \Order::getByReference($primary_order->reference);
        foreach ($orders as $object) {
            $order = new \Order($object->id);
            if ($order->hasBeenShipped()) {
                return true;
            }
        }

        return false;
    }

    public function merchantIdsToReport()
    {
        $sql = 'SELECT distinct merchant_id FROM ' . _DB_PREFIX_ . 'sequra_order WHERE sent_to_sequra = 0';
        $assoc = \Db::getInstance()->executeS($sql);
        $res = [];
        if (count($assoc) == 0) {
            return [\Configuration::get('SEQURA_MERCHANT_ID_ES')];
        }
        foreach ($assoc as $row) {
            $res[] = $row['merchant_id'] ? $row['merchant_id'] : \Configuration::get('SEQURA_MERCHANT_ID_ES');
        }

        return $res;
    }

    public function orderIdsToReport($merchant_id)
    {
        $sql = 'SELECT order_id
            FROM ' . _DB_PREFIX_ . 'sequra_order
            WHERE
                sent_to_sequra = 0
            AND merchant_id="' . addslashes($merchant_id) . '"';
        $assoc = \Db::getInstance()->executeS($sql);
        $res = [];
        foreach ($assoc as $row) {
            $res[] = $row['order_id'];
        }

        return $res;
    }

    public function statsToReport($days)
    {
        $sql = 'SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE date_add > DATE_SUB(NOW(), INTERVAL ' . $days . ' day)';
        $assoc = \Db::getInstance()->executeS($sql);
        $res = [];
        foreach ($assoc as $row) {
            $res[] = new \Order($row['id_order']);
        }

        return $res;
    }

    private function dequeueOrders($order_ids)
    {
        if (empty($order_ids)) {
            return;
        }
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'sequra_order SET sent_to_sequra = 1 WHERE order_id IN ('
            . join(',', $order_ids) . ')';

        return \Db::getInstance()->execute($sql);
    }

    public static function addMessageToOrder($order_ids)
    {
        foreach ($order_ids as $id) {
            $order = new \Order((int) $id);
            self::addPrivateMessage($order);
        }
    }

    private static function addPrivateMessage($order)
    {
        // Add this message in the customer thread
        $customer_thread = new \CustomerThread();
        $customer_thread->id_contact = 0;
        $customer_thread->id_customer = (int) $order->id_customer;
        $customer_thread->id_shop = (int) $order->id_shop;
        $customer_thread->id_order = (int) $order->id;
        $customer_thread->id_lang = (int) $order->id_lang;
        $customer_thread->status = 'closed';
        $customer_thread->token = \Tools::passwdGen(12);
        $customer_thread->add();

        $customer_message = new \CustomerMessage();
        $customer_message->id_customer_thread = $customer_thread->id;
        $customer_message->id_employee = 0;
        $customer_message->message = self::SEQURA_DR_SENT_TXT;
        $customer_message->private = true;

        if (!$customer_message->add()) {
            $logger = new \PrestaShopLogger();
            $logger->addLog('An error occurred while saving message');
        }
    }

    public function getReportBuilder($merchant_id, $orders, $stats)
    {
        return new ReportBuilder($merchant_id, $orders, $stats);
    }
}
