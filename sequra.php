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
require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings;
use PrestaShop\Module\PrestashopSequra\Configuration\Settings;
use PrestaShop\Module\PrestashopSequra\Crontab;
use PrestaShop\Module\PrestashopSequra\Identification;
use PrestaShop\Module\PrestashopSequra\Installer;
use PrestaShop\Module\PrestashopSequra\OrderBuilder;
use PrestaShop\Module\PrestashopSequra\OrderUpdater;
use PrestaShop\Module\PrestashopSequra\PreQualifier;
use PrestaShop\Module\PrestashopSequra\ProductExtra;
use PrestaShop\Module\PrestashopSequra\PSOrderUpdater;
use PrestaShop\Module\PrestashopSequra\SequraTools;
use PrestaShop\Module\PrestashopSequra\Translations;

define('SEQURA_ERROR_PAYMENT', 100);
define('SEQURA_ERROR_CART_CHANGED', 200);
/**
 * Core module for SeQura Payment
 */
class Sequra extends PaymentModule
{
    /**
     * External entry points
     **/
    const CSS_FILE = 'custom.css';
    public static $user_agent = null;
    // Protect against unexpected callers
    public static $thirdPartyOnePagers = array(
        'onepagecheckout',
        'onepagecheckoutps',
        'esp_1stepcheckout',
        'threepagecheckout',
    );
    public $secret_handshake = false;
    public $qualifier;
    public $limited_countries;
    public $displayName;

    protected static $instance;
    protected $builder;
    protected $valid_api_credentials = null;
    protected $valid_api_for_merchant = [];
    /**
     * @var \Sequra\PhpClient\Client|null
     */
    private $client = null;
    /**
     * @var \PrestaShop\Module\PrestashopSequra\Reporter|null
     */
    private $reporter = null;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            new Sequra();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->name = 'sequra';
        $this->tab = 'payments_gateways';
        $this->version = '6.0.0';
        $this->author = 'seQura Tech';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->limited_countries = array('es', 'fr', 'it', 'pt');
        $this->need_instance = 0;
        parent::__construct();
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '9.0');
        $this->description = $this->l('General configuration for seQura payment methods');
        $this->displayName = $this->l('seQura');
        $this->module_key = '8e96d6d25be17de4e9fc5a71d81ecb21';
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        self::$instance = $this;
    }

    public static function needsBasicPresentation()
    {
        foreach (self::$thirdPartyOnePagers as $name) {
            if (SequraTools::isModuleActive($name)) {
                return $name;
            }
        }

        return false;
    }

    public function getAllowedCountries()
    {
        return $this->limited_countries;
    }

    public function getAllowedLanguages()
    {
        return array('es', 'eu', 'ca', 'gl', 'fr', 'it', 'pt');
    }

    public function getAllowedCurrencies()
    {
        return array('EUR');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        $installer = new Installer($this);
        return $installer->install();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $installer = new Installer($this);
        if (!$installer->uninstall()) {
            return false;
        }

        return parent::uninstall();
    }

    public function enable($force_all = false)
    {
        parent::enable($force_all);
        $countries = $this->getCountries();
        array_walk(
            $countries,
            ['\PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings', 'updateActivePaymentMethods']
        );
        return true;
    }

    public function disable($force_all = false)
    {
        parent::disable($force_all);
        $countries = $this->getCountries();
        array_walk(
            $countries,
            function ($country) {
                \Configuration::updateValue('SEQURA_ACTIVE_METHOD_' . $country, serialize([]));
            }
        );
        return true;
    }

    public function refuse()
    {
        echo '<p>' .
            $this->l('To pay with seQura, the same client has to go through the payment gateway.') .
            '</p>';
        exit;
    }

    public function addCSS($uri)
    {
        $server = 'remote';
        if (strpos($uri, __PS_BASE_URI__) === 0) {
            $uri = substr($uri, strlen(__PS_BASE_URI__));
            $server = 'local';
        }

        return $this->context->controller->registerStylesheet(
            sha1($this->version . $uri),
            $uri,
            array('media' => 'all', 'priority' => 80, 'server' => $server)
        );
    }

    public function addJS($uri, $priority = 80)
    {
        $server = 'remote';
        if (strpos($uri, __PS_BASE_URI__) == 0) {
            $uri = substr($uri, strlen(__PS_BASE_URI__));
            $server = 'local';
        }

        return $this->context->controller->registerJavascript(
            sha1($this->version . $uri),
            $uri,
            array('position' => 'bottom', 'priority' => $priority, 'server' => $server)
        );
    }

    // HOOKS
    public function hookHeader($params)
    {
        return $this->hookDisplayHeader($params);
    }

    public function hookDisplayHeader($params)
    {
        Crontab::poolCron();
        $scriptBaseUri = self::getScriptBaseUri();
        $config = new Settings($this);
        if (file_exists($config->getCustomCssPath())) {
            $this->context->controller->addCSS($this->_path . 'views/css' . self::CSS_FILE);
        }
        $this->addJS($this->_path . 'views/js/checkout.js');
        $inferred_country = self::inferCountry();
        if (!in_array(
            $inferred_country,
            $this->getCountries()
        )) {
            return;
        }
        $this->context->smarty->assign(
            array(
                'merchant' => \Configuration::get('SEQURA_MERCHANT_ID_' . $inferred_country),
                'assetKey' => \Configuration::get('SEQURA_ASSETS_KEY'),
                'sequra_products' => unserialize(Configuration::get('SEQURA_ACTIVE_METHODS_' . $inferred_country)),
                'scriptBaseUri' => $scriptBaseUri,
                'locale' => $this->context->language->iso_code,
            )
        );
        $formatter = new PrestaShop\PrestaShop\Adapter\Product\PriceFormatter();
        $narrow_no_break_space = "\u{202F}";
        $format = $formatter->format(1234.56); // @todo: Find smarter way to get decimal separator
        $format = str_replace($narrow_no_break_space, " ", $format);
        preg_match('/1([^\d]*)234([^\d]*)56/', $format, $sep);
        $this->context->smarty->assign(
            array(
                'decimalSeparator' => (isset($sep[2]) && $sep[2]) ? $sep[2] : ',',
                'thousandSeparator' => isset($sep[1]) ? $sep[1] : '',
            )
        );

        $tpl = 'header.tpl';
        return $this->display(__FILE__, 'views/templates/front/' . $tpl);
    }

    public function setVariables($cart, $payment_method = null)
    {
        $linker = $this->context->link;
        $ajax_form_url = $linker->getModuleLink(
            $this->name,
            'getidentificationform',
            array(),
            true
        );
        $form_url = $linker->getModuleLink(
            $this->name,
            'identification',
            array(),
            true
        );
        $vars = array(
            'ajax_form_url' => $ajax_form_url,
            'module_id' => $this->id,
            'method' => $this->name,
            'form_url' => $form_url,
            'total_price' => $cart->getOrderTotal(),
            'call_to_action_text' => $this->getCallToActionText(),
            'sequrapayment_js' => __PS_BASE_URI__ . 'modules/sequra/js/checkout.js'
        );
        $this->context->smarty->assign($vars);
        if ($payment_method) {
            $vars = array(
                'payment_method' => $payment_method,
            );
            $this->context->smarty->assign($vars);
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = isset($params['id_product']) ? $params['id_product'] : \Tools::getValue('id_product');
        $sq_product_extra = new ProductExtra($id_product);
        $this->context->smarty->assign(
            array(
                'sequra_is_banned' => (bool)$sq_product_extra->getProductIsBanned(),
                'sequra_for_services' => (bool)Configuration::get('SEQURA_FOR_SERVICES'),
                'sequra_allow_registration_items' => (bool)Configuration::get('SEQURA_ALLOW_REGISTRATION_ITEMS'),
                'sequra_allow_payment_delay' => (bool)Configuration::get('SEQURA_ALLOW_PAYMENT_DELAY'),
                'sequra_is_service' => (bool)$sq_product_extra->getProductIsService(),
                'sequra_service_end_date' => $sq_product_extra->getProductServiceEndDate(),
                'sequra_desired_first_charge_date' => $sq_product_extra->getProductFirstChargeDate(),
                'sequra_registration_amount' => $sq_product_extra->getProductRegistrationAmount(),
                'ISO8601_PATTERN' => SequraTools::ISO8601_PATTERN
            )
        );
        $tpl = 'productsextra.tpl';

        return $this->display(__FILE__, 'views/admin/' . $tpl);
    }

    public function hookActionProductUpdate($params)
    {
        $sq_product_extra = new ProductExtra($params['id_product']);
        $sq_product_extra->save($this);
    }

    public function hookPostUpdateOrderStatus($params)
    {
        return $this->hookActionOrderStatusPostUpdate($params);
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if ($params['newOrderStatus']->shipped) {
            $updater = OrderUpdater::getInstance($this, $params['id_order']);
            $updater->orderUpdateIfNeeded();
        }
        if (!\Configuration::get('SEQURA_SEND_CANCELLATIONS')) {
            return;
        }
        $canceller = PSOrderUpdater::getInstance('admin', $params['id_order']);
        if ($canceller->checkIfPreconditionsAreMissing($params['newOrderStatus']->id)) {
            return;
        }
        $canceller->cancelWithSequra(
            $params['cart'] ?: new Cart(Order::getCartIdStatic($params['id_order']))
        );
    }

    public function hookDisplayAdminOrder($params)
    {
        $order = new \Order((int) \Tools::getValue('id_order'));
        if (strpos($order->module, 'sequra') === false &&
            count($order->getOrderPayments()) >= 1
        ) {
            return;
        }
        $tpl = 'sequrapayment_adminorder.tpl';
        if (count($order->getOrderPayments()) >= 1) {
            $endpoint = (
                Configuration::get('SEQURA_MODE') != 'live' ?
                'https://simbox.sequrapi.com/orders/' : 'https://simba.sequra.es/orders/'
            );
            $payments = $order->getOrderPayments();
            $uuid = $payments[0]->transaction_id;
            $this->context->smarty->assign(array(
                'simba_link' => $endpoint . $uuid
            ));
            $tpl = 'sequrapayment_adminorder_simbalink.tpl';
        } else {
            $this->context->smarty->assign(array(
                'send_payment_button' => true
            ));
            $tpl = 'sequrapayment_adminorder_paymentbutton.tpl';
        }
        return $this->display(__FILE__, './views/templates/admin/' . $tpl);
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return null;
        }
        SequraTools::removeSequraOrderFromSession();
        $id_cart = (int) Tools::getValue('id_cart', 0);
        $order = SequraTools::getOrderByCartId($id_cart);
        $vars = array(
            'service_name' => $this->getDisplayName(),
            'order_state' => $order->current_state,
            'id_order' => $order->id,
            'on_hold' => $order->current_state == \Configuration::get('SEQURA_OS_NEEDS_REVIEW'),
            'shop_name' => $this->context->shop->name,
        );
        $this->context->smarty->assign($vars);
        if (isset($order->reference) && !empty($order->reference)) {
            $this->smarty->assign('reference', $order->reference);
        }

        return $this->renderView('payment_return');
    }

    public function hookDisplayHome($params)
    {
        // return $this->renderWidget('home', $params);
        // @TODO: Implement this
    }

    public function hookPayment($params)
    {
        if (!$this->active || !PreQualifier::availableForIP()) {
            return;
        }
        $qualifier = new PreQualifier($params['cart']);
        if (!$qualifier->passes()) {
            return;
        }
        $this->setVariables($params['cart']);
        $payment_methods = $this->getPaymentMethods();
        array_walk(
            $payment_methods,
            function (&$method) {
                $method['icon'] = $this->getLogoUlr($method['icon']);
            }
        );
        $vars = array(
            'payment_methods' => $payment_methods,
        );
        $this->context->smarty->assign($vars);
        return $this->renderPaymentForm($params);
    }

    // PS 1.7
    public function hookPaymentOptions($params)
    {
        // In this case checking IP is enogh everith else should be payment_methods API responsability.
        if (!$this->active || !PreQualifier::availableForIP()) {
            return;
        }
        $qualifier = new PreQualifier($params['cart']);
        if (!$qualifier->passes()) {
            return;
        }
        $payment_methods = $this->getPaymentMethods();
        if (!$payment_methods || count($payment_methods) < 1) {
            return;
        }
        $payment_options = [];
        foreach ($payment_methods as $payment_method) {
            $payment_options[] = $this->addPaymentOption($params['cart'], $payment_method);
        }
        return $payment_options;
    }

    public function hookDisplayFooter($params)
    {
        if (Tools::getValue('RESET_SEQURA_ACTIVE_METHODS') == 'true') {
            $countries = $this->getCountries();
            array_walk(
                $countries,
                ['\PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings', 'updateActivePaymentMethods']
            );
        }
        $country_code = strtoupper(substr($this->context->language->iso_code, -2));
        if (
            !in_array(
                $country_code,
                $this->getCountries()
            )
        ) {
            return;
        }
        $this->context->smarty->assign(
            array(
                'css_selector_price' => \Configuration::get('SEQURA_CSS_SEL_PRICE'),
                'sq_max_amount' => \Configuration::get('SEQURA_PARTPAYMENT_PRODUCT_MAX_AMOUNT_' . $country_code),
                'sq_pp_product' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_PRODUCT',
                    \Configuration::get('SEQURA_PARTPAYMENT_PRODUCT')
                ),
                'sq_categories_show' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CATEGORIES_SHOW',
                    \Configuration::get('SEQURA_PARTPAYMENT_CATEGORIES_SHOW')
                ),
                'sq_categories_css_sel_price' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CATEGORIES_CSS_SEL_PRICE',
                    \Configuration::get('SEQURA_PARTPAYMENT_CATEGORIES_CSS_SEL_PRICE')
                ),
                'sq_categories_css_sel' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CATEGORIES_CSS_SEL',
                    \Configuration::get('SEQURA_PARTPAYMENT_CATEGORIES_CSS_SEL')
                ),
                'sq_categories_msg' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CATEGORIES_TEASER_MSG',
                    \Configuration::get('SEQURA_PARTPAYMENT_CATEGORIES_TEASER_MSG')
                ),
                'sq_cart_show' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CART_SHOW',
                    \Configuration::get('SEQURA_PARTPAYMENT_CART_SHOW')
                ),
                'sq_cart_css_sel_price' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CART_CSS_SEL_PRICE',
                    \Configuration::get('SEQURA_PARTPAYMENT_CART_CSS_SEL_PRICE')
                ),
                'sq_cart_css_sel' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CART_CSS_SEL',
                    \Configuration::get('SEQURA_PARTPAYMENT_CART_CSS_SEL')
                ),
                'sq_cart_msg' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CART_TEASER_MSG',
                    \Configuration::get('SEQURA_PARTPAYMENT_CART_TEASER_MSG')
                ),

                'sq_minicart_show' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_MINICART_SHOW',
                    \Configuration::get('SEQURA_PARTPAYMENT_MINICART_SHOW')
                ),
                'sq_minicart_css_sel_price' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_MINICART_CSS_SEL_PRICE',
                    \Configuration::get('SEQURA_PARTPAYMENT_MINICART_CSS_SEL_PRICE')
                ),
                'sq_minicart_css_sel' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_MINICART_CSS_SEL',
                    \Configuration::get('SEQURA_PARTPAYMENT_MINICART_CSS_SEL')
                ),
                'sq_minicart_msg' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_MINICART_TEASER_MSG',
                    \Configuration::get('SEQURA_PARTPAYMENT_MINICART_TEASER_MSG')
                ),
                'sq_categories_msg_below' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CATEGORIES_BELOW_MSG',
                    \Configuration::get('SEQURA_PARTPAYMENT_CATEGORIES_BELOW_MSG')
                ),
                'sq_cart_msg_below' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_CART_BELOW_MSG',
                    \Configuration::get('SEQURA_PARTPAYMENT_CART_BELOW_MSG')
                ),
                'sq_minicart_msg_below' => $this->getConfigValue(
                    'SEQURA_' . $country_code . '_PARTPAYMENT_MINICART_BELOW_MSG',
                    \Configuration::get('SEQURA_PARTPAYMENT_MINICART_BELOW_MSG')
                ),
                'widgets' => [],
            )
        );
        if ($this->context->controller instanceof ProductController) {
            $this->context->smarty->assign(
                'widgets',
                $this->getWidgetsForProductPage(
                    $this->context->controller->getProduct()->id
                )
            );
        }
        $tpl = 'footer.tpl';
        return $this->display(__FILE__, 'views/templates/front/' . $tpl);
    }

    public function hookDisplayFooterProduct($params)
    {
        return $this->hookDisplayFooter($params);
    }

    public function hookDisplayProductButtons($params)
    {
        return $this->hookDisplayFooter($params);
    }

    public function hookModuleRoutes($params)
    {
        return array(
            'sequra-getidentificationform' => array(
                'controller' => 'getidentificationform',
                'rule' => 'sequra/form/{product}',
                'keywords' => array(
                    'product' => array('regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'module'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                    'controller' => 'getidentificationform',
                ),
            ),
        );
    }

    protected static function getConfigValue(
        $key,
        $default = false,
        $id_lang = null,
        $id_shop_group = null,
        $id_shop = null
    ) {
        return \Configuration::get($key, $id_lang, $id_shop_group, $id_shop, $default);
    }

    private function getWidgetsForProductPage($id_product)
    {
        $ret = array();
        if ($this->isProductPage() && PreQualifier::canDisplayWidgetInProductPage($id_product)) {
            /*
            * @var $controller \ProductController
            */
            $controller = $this->context->controller;
            if(! $controller instanceof \ProductController){
                return $ret;
            }
            $price = $controller->getProduct()->getPrice();
            $country_code = self::inferCountry();
            $ret = array_map(
                function ($method) use ($country_code) {
                    $method['country'] = $country_code;
                    $i18nproduct = \PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings::buildUniqueI18ProductCode($method);
                    return array(
                        'css_sel' => \Configuration::get('SEQURA_' . $i18nproduct . '_CSS_SEL'),
                        'product' => $method['product'],
                        'theme' => \Configuration::get('SEQURA_' . $i18nproduct . '_WIDGET_THEME'),
                        'campaign' => isset($method['campaign']) ? $method['campaign'] : '',
                    );
                },
                array_filter(
                    \PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings::getMerchantPaymentMethods(false, $country_code),
                    function ($method) use ($price) {
                        return
                            \PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings::getFamilyFor($method) != 'CARD' &&
                            PreQualifier::isDateInRange($method) &&
                            PreQualifier::isPriceWithinMethodRange(
                                $method,
                                $price,
                                \PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings::getFamilyFor($method) != 'PARTPAYMENT'
                            );
                    }
                )
            );
        }
        $ret = array_unique($ret, SORT_REGULAR);
        return array_reverse($ret);
    }

    private function getPaymentOptionAction($payment_method)
    {
        $linker = $this->context->link;
        $params = [
            'product' => $payment_method['product']
        ];
        if (isset($payment_method['campaign'])) {
            $params['campaign'] = $payment_method['campaign'];
        }
        if (Configuration::get('SEQURA_FORCE_NEW_PAGE') == 1) {
            return $linker->getModuleLink(
                $this->name,
                'identification',
                $params,
                true
            );
        }
        $ajax_form_url = $linker->getModuleLink(
            $this->name,
            'getidentificationform',
            [],
            true
        );
        return "javascript:SequraIdentificationPopupLoader.url = '$ajax_form_url';" .
            "SequraIdentificationPopupLoader.product = '" . $payment_method['product'] . "';" .
            "SequraIdentificationPopupLoader.campaign = '" . $payment_method['campaign'] . "';" .
            "SequraIdentificationPopupLoader.closeCallback = SequraIdentificationPopupLoader.closeCallback = function() {window.location.reload();};" .
            "SequraIdentificationPopupLoader.showForm();";
    }

    private function addPaymentOption($cart, $payment_method)
    {
        $this->setVariables($cart, $payment_method);

        $settings = new Settings($this);
        $sequraOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $sequraOption
            ->setModuleName($this->name)
            ->setCallToActionText($payment_method['long_title'] . (
                    isset($payment_method['cost_description']) ?
                        ' ' . $payment_method['cost_description'] :
                        ''
                    ))
            ->setAction($this->getPaymentOptionAction($payment_method))
            ->setAdditionalInformation(
                $this->context->smarty->fetch(
                    $settings->getPaymentFormTplPath()
                )
            )->setLogo($this->getLogoUlr($payment_method['icon']));
        return $sequraOption;
    }
    // END hooks and rendering

    public function getClient()
    {
        if ($this->client instanceof \Sequra\PhpClient\Client) {
            return $this->client;
        }
        \Sequra\PhpClient\Client::$user_agent =
            'cURL PrestaShop ' . _PS_VERSION_ . ' plugin v' . $this->version . ' php ' . phpversion();
        $this->client = new \Sequra\PhpClient\Client(
            \Configuration::get('SEQURA_USER'),
            \Configuration::get('SEQURA_PASS'),
            \Configuration::get('SEQURA_MODE') == 'live' ?
                \Configuration::get('SEQURA_LIVE_ENDPOINT') : \Configuration::get('SEQURA_SANDBOX_ENDPOINT'),
            \Configuration::get('SEQURA_MODE') != 'live'
        );

        return $this->client;
    }

    public function getConfigJson()
    {
        $settings = new Settings($this);
        $config = [];
        foreach ($settings->getConfigKeys() as $key) {
            $config[$key] = \Configuration::get($key, null, null, null, '');
        }
        $config['valid_credentials'] = $this->validApiCredentials();
        foreach ($this->getCountries(true) as $country) {
            $config['valid_credentials_' . $country] = $this->validApiForMerchant(
                isset($config['SEQURA_MERCHANT_ID_' . $country]) ? $config['SEQURA_MERCHANT_ID_' . $country] : false
            );
        }
        $config['SEQURA_PASS'] = '********';
        return json_encode($config);
    }

    private function validApiCredentials()
    {
        if (is_null($this->valid_api_credentials)) {
            $this->valid_api_credentials = $this->getClient()->isValidAuth();
        }
        return $this->valid_api_credentials;
    }

    private function validApiForMerchant($merchant_ref)
    {
        if (!isset($this->valid_api_for_merchant[$merchant_ref])) {
            $this->valid_api_for_merchant[$merchant_ref] =
                $merchant_ref &&
                $this->getClient()->isValidAuth($merchant_ref);
        }
        return $this->valid_api_for_merchant[$merchant_ref];
    }

    public function getContent()
    {
        Media::addJsDef([
            'sequra_configured' => $this->getClient()->isValidAuth(),
            'sequra_version' => $this->version,
            'sequra_config_endpoint' => $this->getAjaxEndpoint(),
            'browser_ip' => $_SERVER['REMOTE_ADDR'],
        ]);
        $this->context->smarty->assign([
            'sequra_messages' => $this->getTranslations(),
            'sequra_config' => $this->getConfigJson(),
            'pathApp' => $this->getPathUri() . 'views/js/app.js',
            'chunkVendor' => $this->getPathUri() . 'views/js/chunk-vendors.js',
            'order_statuses' => json_encode(
                \OrderState::getOrderStates($this->context->language->id)
            ),
        ]);
        return $this->display(__FILE__, '/views/templates/admin/app.tpl');
    }

    public function getProduct()
    {
        return \Tools::getValue('product', '');
    }

    public function getCampaign()
    {
        return \Tools::getValue('campaign', '');
    }

    public function getDisplayName()
    {
        if (\Tools::getValue('product_code', false)) {
            return PaymentMethodsSettings::getTitleFromUniqueProductCode(
                \Tools::getValue('product_code'),
                \Tools::getValue('order_ref', null)
            );
        }
        return $this->displayName;
    }

    public function setAllowedCountries($shop = null)
    {
        $allowed_countries = $this->getCountries();

        $shops = is_null($shop) ?
            \Shop::getShops(true, null, true) : [1 => $shop];
        $db = \Db::getInstance();
        foreach ($shops as $key => $shop_id) {
            $db->delete('module_country', 'id_module = ' . (int)$this->id . ' and id_shop = ' . (int)$shop_id);
            foreach ($allowed_countries as $country) {
                $db->insert(
                    'module_country',
                    array(
                        'id_module' => (int)$this->id,
                        'id_country' => (int)Country::getByIso($country),
                        'id_shop' => (int)$shop_id
                    )
                );
            }
        }
    }

    public function setAllowedCurrencies()
    {
        $allowed_currencies = 'EUR'; //"EUR,USD" → for more than 1

        $shops = \Shop::getShops(true, null, true);

        foreach ($shops as $s) {
            if (!\Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'module_currency`
                WHERE id_module = ' . (int)$this->id . '
                and `id_currency` not in
                    (SELECT `id_currency` FROM `' . _DB_PREFIX_ . 'currency`
                    WHERE iso_code = "' . $allowed_currencies . '")'
            )) {
                return false;
            }
        }
    }

    private function getTranslations()
    {
        $translations = new Translations($this);
        return json_encode($translations->getTranslations());
    }

    public function getAjaxEndpoint()
    {
        return $this->context->link->getAdminLink('AdminAjaxSequra');
    }

    public function getPsProduct($id_product = null, $full = true, $id_lang = null)
    {
        $id_product |= \Tools::getValue('id_product');
        $id_lang    |= \Context::getContext()->language->id;

        return new Product($id_product, $full, $id_lang);
    }

    public function getModuleViewsDirectory()
    {
        return _PS_MODULE_DIR_ . $this->name . '/views';
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getLocalPath()
    {
        return $this->local_path;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getCallToActionText()
    {
        return \Configuration::get($this->getConfigName() . '_NAME');
    }

    public function getConfigName()
    {
        return strtoupper(str_replace('sequra', 'sequra_', $this->name));
    }

    public static function getScriptBaseUri()
    {
        return \Configuration::get('SEQURA_MODE') == 'live' ?
            \Configuration::get('SEQURA_LIVE_SCRIPT_BASE_URI') : \Configuration::get('SEQURA_SANDBOX_SCRIPT_BASE_URI');
    }

    public function getCountries(bool $all = false): array
    {
        return $all ?
            array_map('strtoupper', $this->limited_countries) :
            explode(',', strtoupper(\Configuration::get('SEQURA_COUNTRIES') ?: ""));
    }

    public static function inferCountry(): string
    {
        return strtoupper(substr(\Context::getContext()->language->iso_code, -2));
    }

    // Build logo URL
    private function getLogoUlr($icon)
    {
        if (substr($icon, 0, 4) === "http") {
            return $icon;
        } else {
            return 'data:image/svg+xml;base64,' . base64_encode($icon);
        }
    }

    private function getPaymentMethods()
    {
        $name = $this->name . '_order';
        $identification = new Identification($this);
        if ($identification->sequraIsReady()) {
            $client = $this->getClient();
            $uri = $this->context->cookie->$name;
            $client->getPaymentMethods($uri);
            if ($client->succeeded()) {
                $json = $client->getJson();
                return array_reduce(
                    $json['payment_options'],
                    function ($methods, $family) {
                        return array_merge($methods, $family['methods']);
                    },
                    []
                );
            }
        }
    }

    protected function isProductPage()
    {
        if ($this->context->controller instanceof ProductController) {
            return true;
        }
        return false;
    }

    private function renderView($name)
    {
        return $this->display(__FILE__, 'views/templates/front/' . $name . '.tpl');
    }

    protected function renderPaymentForm($params)
    {
        switch (Tools::getValue('sequra_error')) {
            case SEQURA_ERROR_CART_CHANGED:
                $vars['sequra_error'] = 'cart_changed';
                break;
            case SEQURA_ERROR_PAYMENT:
                $vars['sequra_error'] = 'payment_error';
                break;
            default:
                $vars['sequra_error'] = false;
        }
        $vars['opc_module'] = SequraTools::needsBasicPresentation();
        $this->context->smarty->assign($vars);
        $this->setVariables($params['cart']);
        $settings = new Settings($this);
        return $this->renderView(
            basename($settings->getPaymentFormTplPath(), ".tpl")
        );
    }

    public function getOrderBuilder($merchant_id = null)
    {
        if (!$this->builder) {
            $this->builder = new OrderBuilder($merchant_id, $this->context->cart, $this);
        }

        return $this->builder;
    }

    public function l($string, $specific = false, $locale = null)
    {
        if (static::$_generate_config_xml_mode) {
            return $string;
        }
        $scape = $locale == 'do_not_scape' ? false : true;
        $locale = $locale == 'do_not_scape' ? null : $locale;
        return Translate::getModuleTranslation(
            $this,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            null,
            true,
            $scape
        );
    }
}
