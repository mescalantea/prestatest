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

define('SEQURA_HOURS_MERCHANT_SHOULD_WAIT', 2);

class PSOrderUpdater
{
    private $order;
    private $client;
    private $webhook;
    /**
     * @var \Sequra|false
     */
    private $module;
    /**
     * @var array
     */
    private $sequra_order;
    /**
     * @var \Cart
     */
    private $cart;
    private $context;

    /**
     * Call this method to get singleton
     *
     * @return PSOrderUpdater
     */
    public static function getInstance($context_or_webhook, $order_id = null)
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new PSOrderUpdater($context_or_webhook, $order_id);
        }

        return $inst;
    }

    private function __construct($context_or_webhook, $order_id)
    {
        $this->context = $context_or_webhook;
        if ('webhook' == $context_or_webhook) {
            $this->initFromWebhook();
        } else {
            $this->initFromContext($order_id);
        }
        $this->module = \Sequra::getInstance();
        $this->client = $this->module->getClient();
    }

    public function initFromWebhook()
    {
        $cart_id = \Tools::getValue('m_cart_id');
        if (SequraTools::sign($cart_id) != \Tools::getValue('m_signed')) {
            echo 'no';
            exit;
        }
        $this->webhook = true;
        $order_ref_1 = \Tools::getValue('order_ref_1');
        if (\Configuration::get('SEQURA_ORDER_ID_FIELD') == '1') {
            $this->order = new \Order($order_ref_1);
        } else {
            foreach (\Order::getByReference($order_ref_1) as $order) {
                // @todo this might fail if the order has been split
                $this->order = $order;
                break;
            }
        }
    }

    public function initFromContext($order_id)
    {
        $this->order = new \Order($order_id);
    }

    public function processCancellationRequest()
    {
        $this->checkIfPreconditionsAreMissing(\Configuration::get('SEQURA_OS_CANCELED')); // or abort if it is a webhook
        $this->cancelOrder();
        $this->checkIfPreconditionsAreMissing($this->order->getCurrentState());
        $cart = new \Cart(\Order::getCartIdStatic($this->order->id));
        $this->cancelWithSequra($cart);
        exit(json_encode(['result' => 'cancelled']));
    }

    public function cancelOrder()
    {
        foreach (\Order::getByReference($this->order->reference) as $order) {
            $history = new \OrderHistory();
            $history->id_order = (int) $order->id;
            $history->changeIdOrderState(
                (int) \Configuration::get('SEQURA_OS_CANCELED'),
                (int) $order->id
            );
            $history->addWithemail();
            $history->save();
        }
    }

    public function setRiskLevelToOrder()
    {
        foreach (\Order::getByReference($this->order->reference) as $order) {
            $history = new \OrderHistory();
            $history->id_order = (int) $order->id;
            $new_status = -1;
            switch (\Tools::getValue('risk_level')) {
                case 'low_risk':
                    $new_status = (int) \Configuration::get('SEQURA_OS_APPROVED_LOWRISK');
                    break;
                case 'high_risk':
                    $new_status = (int) \Configuration::get('SEQURA_OS_APPROVED_HIGHRISK');
                    break;
                case 'under_evaluation':
                    $new_status = (int) \Configuration::get('SEQURA_OS_APPROVED_UNKNOWNRISK');
                    break;
                default:
                    return;
            }
            $history->changeIdOrderState($new_status, (int) $order->id);
            $history->save();
        }
    }

    public function checkIfPreconditionsAreMissing($newOrderStatus)
    {
        if (!$this->wasPaidWithSequra()
            || !in_array(
                $newOrderStatus,
                [
                    \Configuration::get('SEQURA_OS_CANCELLED'),
                    \Configuration::get('SEQURA_OS_CANCELED'),
                    \Configuration::get('SEQURA_PS_CANCELED'),
                ]
            )
        ) {
            return true;
        }
        if ($this->webhook) {
            $this->orderHasBeenShipped();
            $this->orderIsToOld();
        }

        return false;
    }

    public function orderHasBeenShipped()
    {
        if ($this->order->id && $this->order->hasBeenShipped()) {
            $since = (time() - strtotime($this->order->delivery_date)) / 60;
            $this->respondWithError(
                json_encode(['result' => 'toolate', 'since' => (int) $since])
            );
        }
    }

    public function orderIsToOld()
    {
        $tooLateSince = self::getWorkingHours($this->order->date_add, date('c')) - SEQURA_HOURS_MERCHANT_SHOULD_WAIT;
        if ($tooLateSince > 0) {
            $this->respondWithError(
                json_encode(['result' => 'toolate', 'since' => (int) $tooLateSince * 60])
            );
        }
    }

    protected function wasPaidWithSequra()
    {
        $sql = 'SELECT order_id FROM ' . _DB_PREFIX_ . 'sequra_order WHERE order_id = ' . (int) $this->order->id;
        \Db::getInstance()->execute($sql);

        return \Db::getInstance()->numRows() > 0;
    }

    protected function getMerchantIdUsed()
    {
        $sql = 'SELECT merchant_id FROM ' . _DB_PREFIX_ . 'sequra_order WHERE order_id = ' . (int) $this->order->id . ' LIMIT 1';

        return \Db::getInstance()->getValue($sql);
    }

    public function cancelWithSequra($cart)
    {
        $this->cart = $cart;
        $this->prepareSequra();

        $this->client->orderUpdate($this->sequra_order);
        if ($this->client->succeeded()) {
            return true;
        } else {
            // @todo add some comment to the order or log
        }
    }

    public function prepareSequra()
    {
        $builder = new OrderBuilder($this->getMerchantIdUsed(), $this->cart, $this->module);
        $this->sequra_order = $builder->build('cancelled');
        $this->emptyCart();
        $this->sequra_order['merchant_reference'] = ReportBuilder::getMerchantRefs($this->order);
        if (!$this->webhook) {
            $this->sequra_order['cancellation_reason'] = 'customer_cancel';
        }
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

    public static function webhookUrl($cart_id = null)
    {
        $linker = \Context::getContext()->link;
        $params = [];
        if ($cart_id) {
            $params = array_merge($params, self::webhookParams($cart_id));
        }

        return $linker->getModuleLink('sequra', 'webhook', $params, true);
    }

    public static function webhookParams($cart_id)
    {
        $params = ['cart_id' => '' . $cart_id, 'signed' => SequraTools::sign($cart_id)];
        $params['id_shop'] = '' . \Context::getContext()->shop->id;
        $params['id_lang'] = '' . \Context::getContext()->language->id;

        return $params;
    }

    public function respondWithError($err)
    {
        if ($this->webhook) {
            http_response_code(200);
            exit($err);
        } else {
            // @todo add comment to order
        }
    }

    // @todo find a better place for this

    private static function getWorkingHours($initialDate, $finalDate)
    {
        $holidays = [
            '2021-01-01',
            '2022-01-01',
            '2023-01-01',
            '2024-01-01',
            '2025-01-01',
            '2026-01-01', /* Año Nuevo */
            '2021-01-06',
            '2022-01-06',
            '2023-01-06',
            '2024-01-06',
            '2025-01-06',
            '2026-01-06', /* Día de Reyes */
            '2021-04-01',
            '2022-04-15',
            '2023-04-07',
            '2024-03-29',
            '2025-04-18',
            '2026-04-01', /* Viernes Santo */
            '2021-05-01',
            '2022-05-01',
            '2023-05-01',
            '2024-05-01',
            '2025-05-01',
            '2026-05-01', /* Fiesta del Trabajo */
            '2020-08-15',
            '2021-08-15',
            '2022-08-15',
            '2023-08-15',
            '2024-08-15',
            '2025-08-15',
            '2026-08-15', /* Asunción de la Virgen */
            '2020-10-12',
            '2021-10-12',
            '2022-10-12',
            '2023-10-12',
            '2024-10-12',
            '2025-10-12',
            '2026-10-12', /* Día de la Hispanidad */
            '2020-11-01',
            '2021-11-01',
            '2022-11-01',
            '2023-11-01',
            '2024-11-01',
            '2025-11-01',
            '2026-11-01', /* Día de todos los Santos */
            '2020-12-06',
            '2021-12-06',
            '2022-12-06',
            '2023-12-06',
            '2024-12-06',
            '2025-12-06',
            '2026-12-06', /* Día de la Constitución */
            '2020-12-08',
            '2021-12-08',
            '2022-12-08',
            '2023-12-08',
            '2024-12-08',
            '2025-12-08',
            '2026-12-08', /* La Inmaculada Concepción */
            '2020-12-25',
            '2021-12-25',
            '2022-12-25',
            '2023-12-25',
            '2024-12-25',
            '2025-12-25',
            '2026-12-25', /* Natividad del Señor */
        ];   // holidays as array
        $noofholiday = sizeof($holidays);     // no of total holidays

        // create all required date time objects
        $firstdate = \DateTime::createFromFormat('Y-m-d H:i:s', $initialDate);
        $lastdate = \DateTime::createFromFormat('Y-m-d H:i:s', $finalDate);
        if ($lastdate > $firstdate) {
            $first = $firstdate->format('Y-m-d');
            $first = \DateTime::createFromFormat('Y-m-d H:i:s', $first . ' 00:00:00');
            $last = $lastdate->format('Y-m-d');
            $last = \DateTime::createFromFormat('Y-m-d H:i:s', $last . ' 23:59:59');
            $workhours = 0;   // working hours

            for ($i = $first; $i <= $last; $i->modify('+1 day')) {
                $holiday = false;
                for ($k = 0; $k < $noofholiday; ++$k) {   // excluding holidays
                    if ($i == $holidays[$k]) {
                        $holiday = true;
                        break;
                    }
                }
                $day = $i->format('l');
                if ($day === 'Saturday' || $day === 'Sunday') {  // excluding saturday, sunday
                    $holiday = true;
                }

                if (!$holiday) {
                    $ii = $i->format('Y-m-d');
                    $f = $firstdate->format('Y-m-d');
                    $l = $lastdate->format('Y-m-d');
                    if ($l == $f) {
                        $workhours += self::sameday($firstdate, $lastdate);
                    } elseif ($ii === $f) {
                        $workhours += self::firstday($firstdate);
                    } elseif ($l === $ii) {
                        $workhours += self::lastday($lastdate);
                    } else {
                        $workhours += 8;
                    }
                }
            }

            return $workhours;
        }
    }

    private static function sameday($firstdate, $lastdate)
    {
        $fmin = $firstdate->format('i');
        $fhour = $firstdate->format('H');
        $lmin = $lastdate->format('i');
        $lhour = $lastdate->format('H');
        // if ($fhour >= 12 && $fhour < 14) {
        //     $fhour = 14;
        // }
        if ($fhour < 9) {
            $fhour = 9;
        }
        if ($fhour >= 17) {
            $fhour = 17;
        }
        if ($lhour < 9) {
            $lhour = 9;
        }
        // if ($lhour >= 12 && $lhour < 14) {
        //     $lhour = 14;
        // }
        if ($lhour >= 17) {
            $lhour = 17;
        }
        if ($lmin == 0) {
            $min = ((60 - $fmin) / 60) - 1;
        } else {
            $min = ($lmin - $fmin) / 60;
        }

        return $lhour - $fhour + $min;
    }

    private static function firstday($firstdate)
    {
        // calculation of hours of first day
        $stmin = $firstdate->format('i');
        $sthour = $firstdate->format('H');
        if ($sthour < 9) {   // time before morning 8
            $lochour = 9;
        } elseif ($sthour > 17) {
            $lochour = 0;
        // } elseif ($sthour >= 12 && $sthour < 14) {
        //     $lochour = 3;
        } else {
            $lochour = 17 - $sthour;
            // if ($sthour <= 14) {
            //     $lochour -= 2;
            // }
            if ($stmin == 0) {
                $locmin = 0;
            } else {
                $locmin = 1 - ((60 - $stmin) / 60);
            }   // in hours
            $lochour -= $locmin;
        }

        return $lochour;
    }

    private static function lastday($lastdate)
    {
        // calculation of hours of last day
        $stmin = $lastdate->format('i');
        $sthour = $lastdate->format('H');
        if ($sthour >= 17) {   // time after 18
            $lochour = 8;
        } elseif ($sthour < 9) {   // time before morning 8
            $lochour = 0;
        // } elseif ($sthour >= 12 && $sthour < 14) {
        //     $lochour = 4;
        } else {
            $lochour = $sthour - 8;
            $locmin = $stmin / 60;   // in hours
            // if ($sthour > 14) {
            //     $lochour -= 2;
            // }
            $lochour += $locmin;
        }

        return $lochour;
    }
}
