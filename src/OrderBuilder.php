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

class OrderBuilder
{
    protected $delivery_address = null;
    protected $invoice_address = null;
    protected $module = null;
    protected $cart = null;
    protected $merchant_id = null;

    public function __construct($merchant_id, $cart, $module)
    {
        $this->cart = $cart;
        $this->module = $module;
        if (!$merchant_id) {
            $address = $this->deliveryAddress();
            if (!$address) {
                $address = $this->invoiceAddress();
            }
            $this->merchant_id = \Configuration::get('SEQURA_MERCHANT_ID_' . $address['country_code']);
            if (!$this->merchant_id) {
                $this->merchant_id = \Configuration::get(
                    'SEQURA_MERCHANT_ID_' . strtoupper(substr(\Context::getContext()->language->iso_code, -2))
                );
            }
        } else {
            $this->merchant_id = $merchant_id;
        }
    }

    public function build($state = '')
    {
        $order = [
            'merchant' => $this->merchant(),
            'cart' => $this->cartWithItems(),
            'delivery_address' => $this->deliveryAddress(),
            'invoice_address' => $this->invoiceAddress(),
            'customer' => $this->customer(),
            'platform' => $this->platform(),
            'gui' => $this->gui(),
            'state' => $state,
        ];
        $order = $this->fixRoundingProblems($order);
        if (method_exists($this->module, 'customizeBuildedOrder')) {
            $order = $this->module->customizeBuildedOrder($order, $this);
        }

        return $order;
    }

    protected function fixRoundingProblems($order)
    {
        $totals = SequraTools::totals($order['cart']);
        $diff_with_tax = $order['cart']['order_total_with_tax'] - $totals['with_tax'];
        $diff_without_tax = $order['cart']['order_total_without_tax'] - $totals['without_tax'];
        /* Don't correct error bigger than 1 cent per line */
        if (($diff_with_tax == 0 && $diff_without_tax == 0) || count($order['cart']['items']) < abs($diff_with_tax)) {
            return $order;
        }

        $item['type'] = 'discount';
        $item['reference'] = 'Ajuste';
        $item['name'] = 'Ajuste';
        $item['total_without_tax'] = $diff_without_tax;
        $item['total_with_tax'] = $diff_with_tax;
        if ($diff_with_tax > 0) {
            $item['type'] = 'handling';
            $item['tax_rate'] = $diff_without_tax ? round(abs($diff_with_tax * $diff_without_tax) - 1) * 100 : 0;
        }
        $order['cart']['items'][] = $item;

        return $order;
    }

    public function merchant()
    {
        $data = [];
        $linker = \Context::getContext()->link;
        $data['id'] = $this->merchant_id;
        $customer = new \Customer((int) $this->cart->id_customer);
        $params = [
            'id_cart' => (int) $this->cart->id,
            'id_module' => (int) $this->module->id,
            'merchant_id' => $this->merchant_id,
            'key' => $customer->secure_key,
            'sq_product' => 'SQ_PRODUCT_CODE',   // SQ_PRODUCT_CODE will be replaced with product name by server
        ];
        $data['return_url'] = $linker->getModuleLink($this->module->name, 'return', $params, true);
        $data['notify_url'] = OrderConfirmer::ipnUrl();
        $data['notification_parameters'] = OrderConfirmer::ipnParams($this->cart->id);
        $data['notification_parameters']['method'] = $this->module->name;
        $data['notification_parameters']['merchant_id'] = $this->merchant_id;
        $data['events_webhook'] = $this->webhook();
        $data['options'] = $this->options();

        return $data;
    }

    protected function webhook()
    {
        $data = null;
        $data = [
            'url' => PSOrderUpdater::webhookUrl(),
            'parameters' => PSOrderUpdater::webhookParams($this->cart->id),
        ];
        $data['parameters']['method'] = $this->module->name;

        return $data;
    }

    protected function options()
    {
        $data = null;
        if (\Configuration::get('SEQURA_ALLOW_PAYMENT_DELAY')) {
            $data = [
                'desired_first_charge_on' => array_reduce(
                    $this->cart->getProducts(),
                    function ($first_charge_on, $item) {
                        $sq_product_extra = new ProductExtra($item['id_product']);
                        $raw_date = $sq_product_extra->getProductFirstChargeDate();
                        if (substr($raw_date, 0, 1) == 'P') {
                            $date = (new \DateTime())->add(new \DateInterval($raw_date));
                        } else {
                            $date = new \DateTime($raw_date);
                        }
                        if (!$first_charge_on) {
                            return $date->format(\DateTime::ATOM);
                        }

                        return min(
                            $first_charge_on,
                            $date->format(\DateTime::ATOM)
                        );
                    }
                ),
            ];
        }

        return $data;
    }

    public static function getEditLink()
    {
        $linker = \Context::getContext()->link;
        $multi_shipping = (\Tools::getValue('multi-shipping') == 1) ? 1 : 0;
        if (\Configuration::get('PS_ORDER_PROCESS_TYPE') == 0) {
            return $linker->getPageLink('order', true, null, 'step=1&multi-shipping=' . $multi_shipping);
        } else {
            return $linker->getPageLink(
                'order-opc',
                true,
                null,
                'isPaymentStep=true&multi-shipping=' . $multi_shipping
            );
        }
    }

    public static $protectedCartKeys = ['secure_key'];
    public static $standardCartKeys = [
        'created_at' => 'date_add',
        'updated_at' => 'date_upd',
    ];

    public function cartWithItems()
    {
        $data = $this->cart->validateFields($die = false) ? $this->cart->getFields() : [];
        SequraTools::removeProtectedKeys($data, self::$protectedCartKeys);
        SequraTools::translateKeys($data, self::$standardCartKeys, $this->cart);

        $data['cart_ref'] = $this->cart->id;
        $currency = new \Currency((int) $this->cart->id_currency);
        $data['currency'] = $currency->iso_code;
        $carrier = new \Carrier((int) $this->cart->id_carrier, $this->getLangId());
        $data['delivery_method'] = $this->deliveryMethod($carrier);
        $data['gift'] = $this->cart->gift ? true : false;
        $data['order_total_without_tax'] = $data['order_total_with_tax'] =
            SequraTools::integerPrice($this->cart->getOrderTotal(true));

        $data['items'] = array_merge(
            $this->items(),
            $this->discounts($this->cart),
            $this->handlingItems($carrier)
        );
        $data['order_total_without_tax'] = $data['order_total_with_tax'];
        if (\Configuration::get('SEQURA_ALLOW_REGISTRATION_ITEMS')) {
            $this->registrationItems($data);
        }

        return $data;
    }

    protected function registrationItems(&$data)
    {
        $items = $data['items'];
        foreach ($items as $key => $item) {
            if (!isset($item['product_id']) || !$item['product_id']) {
                continue;
            }
            $sq_product_extra = new ProductExtra($item['product_id']);
            $registration_amount = SequraTools::integerPrice(
                $sq_product_extra->getProductRegistrationAmount()
            );
            if ($registration_amount > 0) {
                $data['items'][] = [
                    'type' => 'registration',
                    'reference' => $item['reference'] . '-reg',
                    'name' => 'Reg. ' . $item['name'],
                    'total_with_tax' => $item['quantity'] * $registration_amount,
                ];
                // Fix original item
                $data['items'][$key]['total_with_tax'] = max(
                    0,
                    $data['items'][$key]['total_with_tax'] - $item['quantity'] * $registration_amount
                );
                $data['items'][$key]['price_with_tax'] = max(
                    0,
                    $data['items'][$key]['price_with_tax'] - $registration_amount
                );
            }
        }
    }

    protected function getGiftWrappingPrice()
    {
        $wrapping_fees_tax_inc = $this->cart->getGiftWrappingPrice();

        return (float) \Tools::convertPrice($wrapping_fees_tax_inc, \Context::getContext()->currency);
    }

    public function deliveryMethod($carrier)
    {
        $ret = [];
        if ($carrier->id > 0) {
            $ret = ['name' => $carrier->name];
        } else {
            $ret = ['name' => 'default'];
        }
        if (is_string($carrier->delay)) {
            $ret['days'] = $carrier->delay;
        }

        return $ret;
    }

    public static $protectedItemKeys = ['wholesale_price'];
    public static $priceKeys = [
        'additional_shipping_cost',
        'price',
        'price_attribute',
        'price_wt',
        'total',
        'total_wt',
        'rate',
    ];
    public static $standardItemKeys = [
        'total_without_tax' => 'total',
        'total_with_tax' => 'total_wt',
        'price_without_tax' => 'price',
        'price_with_tax' => 'price_wt',
        'tax_rate' => 'rate',
        'description' => 'description_short',
        'product_id' => 'id_product',
        'manufacturer' => 'id_manufacturer',
    ];

    public static $limitNeedingItemKeys = [
        'productmega' => 0,
        'attributes_small' => 256,
    ];

    public function items()
    {
        // TODO: handle customizations
        $items = [];
        foreach ($this->cart->getProducts() as $item) {
            $sq_product_extra = new ProductExtra($item['id_product']);
            $item['type'] = 'product';
            if ($service_end_date = $sq_product_extra->shouldTreatAsService()) {
                $item['type'] = 'service';
                if (substr($service_end_date, 0, 1) == 'P') {
                    $item['ends_in'] = $service_end_date;
                } else {
                    $item['ends_on'] = $service_end_date;
                }
            }
            SequraTools::removeProtectedKeys($item, self::$protectedItemKeys);
            SequraTools::makeIntegerPrices($item, self::$priceKeys);
            SequraTools::truncateKeys($item, self::$limitNeedingItemKeys);
            SequraTools::translateKeys($item, self::$standardItemKeys);
            if (isset($item['manufacturer'])) {
                $item['manufacturer'] = (string) $item['manufacturer'];
            }
            $item['quantity'] = (int) $item['quantity'];
            if (!$item['reference']) { // There are merchant that don't use products reference
                $item['reference'] = $item['product_id'];
            }
            $item['downloadable'] = isset($item['is_virtual']) && $item['is_virtual'] ? true : false;
            $item['tax_rate'] = 0;
            $item['price_without_tax'] = $item['price_with_tax'];
            $item['total_without_tax'] = $item['total_with_tax'];
            $item['price_without_tax'] = $item['price_with_tax'];
            $item['total_without_tax'] = $item['total_with_tax'];

            $items[] = SequraTools::stripHTML($item);
        }

        return $items;
    }

    public static $protectedDiscountKeys = ['obj'];
    public static $discountPriceKeys = [
        'reduction_percent',
        'reduction_amount',
        'minimum_amount',
        'value_real',
        'value_tax_exc',
    ];
    public static $standardDiscountKeys15 = [
        'reference' => 'code',
        'name' => 'description',
    ];
    public static $standardDiscountKeys14 = [
        'reference' => 'name',
        'name' => 'description',
    ];
    public static $discountItemKeys = [
        'total_without_tax' => 'value_real',
        'total_with_tax' => 'value_real',
    ];

    public static function discounts($cart)
    {
        $items = [];
        $total_discounts = $cart->getOrderTotal(true, \Cart::ONLY_DISCOUNTS);
        if (0 < $total_discounts) {
            $items[] = [
                'type' => 'discount',
                'reference' => 'discount',
                'name' => 'Discount',
                'total_with_tax' => -SequraTools::integerPrice($total_discounts),
            ];
        }

        return $items;
    }

    public function handlingItems($carrier)
    {
        $items = [];
        if ($shipping = $this->cart->getOrderTotal(false, \Cart::ONLY_SHIPPING)) {
            $shipping_wt = $this->cart->getOrderTotal(true, \Cart::ONLY_SHIPPING);
            $tax_rate = 0;

            $delivery_method = $this->deliveryMethod($carrier);
            $items[] = [
                'type' => 'handling',
                'reference' => 'shipping',
                'name' => $delivery_method['name'],
                'tax_rate' => $tax_rate,
                'total_without_tax' => SequraTools::integerPrice($shipping_wt),
                'total_with_tax' => SequraTools::integerPrice($shipping_wt),
            ];
        }
        if ($this->cart->gift && $wrapping_fees = $this->getGiftWrappingPrice()) {
            $tax_rate = 0;
            $items[] = [
                'type' => 'handling',
                'reference' => 'giftwrapping',
                'name' => 'Envoltorio para regalo',
                'tax_rate' => $tax_rate,
                'total_without_tax' => SequraTools::integerPrice($wrapping_fees),
                'total_with_tax' => SequraTools::integerPrice($wrapping_fees),
            ];
        }

        return $items;
    }

    public function deliveryAddress()
    {
        if (!$this->delivery_address) {
            $address = new \Address((int) $this->cart->id_address_delivery);
            $this->delivery_address = $this->address($address);
        }

        return $this->delivery_address;
    }

    public function invoiceAddress()
    {
        if (!$this->invoice_address) {
            $address = new \Address((int) $this->cart->id_address_invoice);
            $this->invoice_address = $this->address($address);
        }

        return $this->invoice_address;
    }

    public static $standardAddressKeys = [
        'given_names' => 'firstname',
        'surnames' => 'lastname',
        'company' => 'company',
        'address_line_1' => 'address1',
        'address_line_2' => 'address2',
        'postal_code' => 'postcode',
        'city' => 'city',
        'phone' => 'phone',
        'mobile_phone' => 'phone_mobile',
        'extra' => 'other',
        'vat_number' => 'vat_number',
    ];

    public static function address($address)
    {
        $data = $address->validateFields($die = false) ? $address->getFields() : [];
        SequraTools::translateKeys($data, self::$standardAddressKeys, $address);
        $country = new \Country((int) $address->id_country);
        $data['country_code'] = $country->iso_code ? $country->iso_code : 'ES';
        if (!isset($data['dni']) || !$data['dni']) {
            $data['dni'] = $data['vat_number'];
        }

        return $data;
    }

    public function customer()
    {
        $id_lang = \Context::getContext()->language->id;
        $customer = new \Customer((int) $this->cart->id_customer);
        $data = self::staticCustomerData($customer);
        if (!$data['language_code']) {
            $lang = new \Language((int) $id_lang);
            $data['language_code'] = $lang->iso_code;
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $data['ip_number'] = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $data['ip_number'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $data['ip_number'] = $_SERVER['REMOTE_ADDR'];
        }
        $data['nin'] = $this->getCustomerNIN($data['given_names']);
        $data['ip_number'] = SequraTools::notNull($data['ip_number']);
        $data['user_agent'] = SequraTools::notNull($_SERVER['HTTP_USER_AGENT']);
        $data['request_uri'] = SequraTools::notNull($_SERVER['REQUEST_URI']);
        $data['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $data['logged_in'] = $this->isLogged($customer);
        $data['previous_orders'] = $this->previousOrders();

        return $data;
    }

    public static $protectedCustomerKeys = ['secure_key', 'passwd', 'last_passwd_gen'];
    public static $standardCustomerKeys = [
        'given_names' => 'firstname',
        'surnames' => 'lastname',
        'date_of_birth' => 'birthday',
        'email' => 'email',
        'created_at' => 'date_add',
        'updated_at' => 'date_upd',
    ];

    private function getCustomerNIN($given_names)
    {
        $delivery_address = $this->deliveryAddress();
        if ($delivery_address['given_names'] == $given_names && $delivery_address['vat_number']) {
            return $delivery_address['vat_number'];
        }
        $invoice_address = $this->invoiceAddress();
        if ($invoice_address['given_names'] == $given_names && $invoice_address['vat_number']) {
            return $invoice_address['vat_number'];
        }

        return null;
    }

    public static function staticCustomerData($customer)
    {
        $data = $customer->validateFields($die = false) ? $customer->getFields() : [];
        SequraTools::removeProtectedKeys($data, self::$protectedCustomerKeys);
        SequraTools::translateKeys($data, self::$standardCustomerKeys, $customer);
        if ($data['date_of_birth'] == '0000-00-00' || $data['date_of_birth'] == '1970-01-01') {
            unset($data['date_of_birth']);
        }
        $data['ref'] = '' . $customer->id;
        $data['language_code'] = '';
        if (isset($customer->id_lang)) {
            $lang = new \Language((int) $customer->id_lang);
            $data['language_code'] = $lang->iso_code;
        }

        return $data;
    }

    public function previousOrders()
    {
        $data = [];
        if ($orders = \Order::getCustomerOrders($this->cart->id_customer)) {
            foreach ($orders as $order) {
                $res = [];
                $currency = new \Currency($order['id_currency']);
                $order_obj = new \Order($order['id_order']);
                $res['currency'] = $currency->iso_code ? $currency->iso_code : 'EUR';
                $res['created_at'] = str_replace(' ', 'T', $order['date_add']);
                $amount = $order['total_paid_tax_incl'];
                $res['amount'] = SequraTools::integerPrice($amount);
                $res['payment_method'] = SequraTools::getPaymentMethod($order['module']);
                $res['payment_method_raw'] = $order['payment'];
                $orderstate = $order_obj->getCurrentOrderState();
                $name = @array_pop($orderstate->name);
                $res['status'] = SequraTools::getOrderStatus($orderstate, $name);
                $res['raw_status'] = is_null($name) ? '' : $name;
                $address = new \Address($order['id_address_delivery']);
                $res['postal_code'] = $address->postcode;
                $data[] = $res;
            }
        }

        return $data;
    }

    public static function isLogged($customer)
    {
        return (bool) $customer->isLogged();
    }

    public function platform()
    {
        $data = [
            'name' => 'PrestaShop',
            'version' => _PS_VERSION_,
            'plugin_version' => $this->module->version,
            'php_version' => phpversion(),
            'php_os' => PHP_OS,
            'uname' => SequraTools::getUname(),
            'db_name' => 'mysql',
            'db_version' => \Db::getInstance()->getVersion(),
        ];

        return $data;
    }

    public function gui()
    {
        return ['layout' => 'desktop'];
    }

    private function getLangId()
    {
        $id_lang = \Context::getContext()->language->id;
        $customer = new \Customer((int) $this->cart->id_customer);

        return (isset($customer->id_lang)) ? (int) $customer->id_lang : (int) $id_lang;
    }

    protected static $partialOrderKeys = ['state', 'merchant', 'merchant_reference', 'platform'];

    public function getPartialOder($order)
    {
        return array_filter(
            $order,
            function ($key) {
                return in_array($key, self::$partialOrderKeys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
