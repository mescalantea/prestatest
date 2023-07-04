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

class Installer
{
    protected $module = null;
    protected $table_name = null;
    protected $db = null;

    protected $_hook_list = [
        'actionOrderStatusPostUpdate',
        'actionProductUpdate',
        'adminOrder',
        'displayAdminOrder',
        'displayAdminProductsExtra',
        'displayFooter',
        'displayHeader',
        'displayHome',
        'footer',
        'header',
        'payment',
        'paymentOptions',
        'paymentReturn',
    ];

    public function __construct($module)
    {
        $this->module = $module;
        $this->table_name = _DB_PREFIX_ . 'sequra_order';
        $this->db = \Db::getInstance();
    }

    protected static function getHomeCategory()
    {
        $id = \Configuration::get('PS_HOME_CATEGORY');

        // PS_HOME_CATEGORY new in PS 1.5; previously it was hardcoded
        return $id ? $id : 1;
    }

    public function install()
    {
        $country = $this->module->inferCountry();
        if (!in_array(strtolower($country), $this->module->getAllowedCountries())) {
            $country = 'ES';
        }
        self::initConfigurationValue('SEQURA_COUNTRIES', strtoupper($country));
        $this->registerHooks();
        $this->registerController('AdminAjaxSequra', 'Config AJAX Endpoint');

        self::initConfigurationValue(
            'SEQURA_USER',
            defined('_SEQURA_USER') ? _SEQURA_USER : strtolower(\Configuration::get('PS_SHOP_NAME'))
        );
        self::initConfigurationValue(
            'SEQURA_PASS',
            defined('_SEQURA_PASS') ? _SEQURA_PASS : 'demoPASSword'
        );
        self::initConfigurationValue(
            'SEQURA_MERCHANT_ID_ES',
            defined('_SEQURA_MERCHANT_ID_ES') ? _SEQURA_MERCHANT_ID_ES : strtolower(\Configuration::get('PS_SHOP_NAME'))
        );
        self::initConfigurationValue('SEQURA_MODE', 'sandbox');
        self::initConfigurationValue(
            'SEQURA_ASSETS_KEY',
            defined('_SEQURA_ASSETS_KEY') ? _SEQURA_ASSETS_KEY : 'xxxxxxxxxx'
        );
        self::initConfigurationValue('SEQURA_LIVE_ENDPOINT', 'https://live.sequrapi.com/');
        self::initConfigurationValue(
            'SEQURA_SANDBOX_ENDPOINT',
            defined('_SEQURA_SANDBOX_ENDPOINT') ? _SEQURA_SANDBOX_ENDPOINT : 'https://sandbox.sequrapi.com/'
        );
        self::initConfigurationValue('SEQURA_LIVE_SCRIPT_BASE_URI', 'https://live.sequracdn.com/assets/');
        self::initConfigurationValue(
            'SEQURA_SANDBOX_SCRIPT_BASE_URI',
            defined('_SEQURA_SANDBOX_SCRIPT_BASE_URI') ? _SEQURA_SANDBOX_SCRIPT_BASE_URI : 'https://sandbox.sequracdn.com/assets/'
        );
        self::initConfigurationValue('SEQURA_FOR_SERVICES', '0');
        self::initConfigurationValue('SEQURA_ALLOW_PAYMENT_DELAY', '0');
        self::initConfigurationValue('SEQURA_ALLOW_REGISTRATION_ITEMS', '0');
        self::initConfigurationValue('SEQURA_FOR_SERVICES_END_DATE', 'P1Y');
        $allowed_ips = array_unique([gethostbyname('proxy-es.dev.sequra.es'), '127.0.0.1', '::1', $_SERVER['REMOTE_ADDR']]);
        self::initConfigurationValue('SEQURA_ALLOW_IP', defined('_SEQURA_USER') ? '' : join(',', $allowed_ips));
        self::initConfigurationValue('SEQURA_AUTOCRON', 1);
        self::initConfigurationValue('SEQURA_AUTOCRON_H', round(rand(2, 8)));
        self::initConfigurationValue('SEQURA_AUTOCRON_M', round(rand(0, 59)));
        self::initConfigurationValue('SEQURA_AUTOCRON_NEXT', Crontab::calcNextExecutionTime());
        self::initConfigurationValue('SEQURA_STATS_ALLOW', 'S');
        self::initConfigurationValue('SEQURA_STATS_AMOUNT', 'S');
        self::initConfigurationValue('SEQURA_STATS_COUNTRIES', 'S');
        self::initConfigurationValue('SEQURA_STATS_PAYMENTMETHOD', 'S');
        self::initConfigurationValue('SEQURA_STATS_STATUS', 'S');
        self::initConfigurationValue(
            'SEQURA_CSS_SEL_PRICE',
            '.product-prices  .current-price'
        );
        self::initConfigurationValue('SEQURA_ORDER_ID_FIELD', 0);

        $this->addOrderState('SEQURA_OS_NEEDS_REVIEW', 'Sequra: en revisión', 'Orange');
        $this->addOrderState('SEQURA_OS_APPROVED', 'Sequra: Aprobado', '#009b5a', false, true, false, true, false, 'payment', true);
        $this->addOrderState('SEQURA_OS_CANCELED', 'Sequra: Cancelado', 'Crimson', false, false, false, true, false, 'order_canceled');
        self::initConfigurationValue('SEQURA_OS_APPROVED_LOWRISK', \Configuration::get('PS_OS_PAYMENT'));
        self::initConfigurationValue('SEQURA_OS_APPROVED_UNKNOWNRISK', \Configuration::get('PS_OS_PAYMENT'));
        self::initConfigurationValue('SEQURA_OS_APPROVED_HIGHRISK', \Configuration::get('PS_OS_PAYMENT'));

        $this->putFirstAmongPaymentMethods();
        $this->module->setAllowedCountries();
        $this->module->setAllowedCurrencies();
        $this->setAllowedCarriers();
        if (\Module::isInstalled('onepagecheckoutps')) {
            $this->onepagecheckoutpsSetUp();
        }
        self::initConfigurationValue('SEQURA_CHECKOUT_SERVICE_NAME', $this->module->l('Pago con seQura','installer'));
        self::initConfigurationValue('SEQURA_FORCE_NEW_PAGE', '0');
        self::initConfigurationValue('SEQURA_CSS_SEL_PRICE', '.product-prices  .current-price');
        self::initConfigurationValue('SEQURA_INVOICE_CSS_SEL', '.product-add-to-cart');
        self::initConfigurationValue('SEQURA_CAMPAIGN_CSS_SEL', '.product-add-to-cart');
        $all_countries = $this->module->getCountries(true);
        array_walk(
            $all_countries,
            [$this, 'initMiniwidgetConfiguration']
        );
        $countries = $this->module->getCountries();
        array_walk(
            $countries,
            ['\PrestaShop\Module\PrestashopSequra\Configuration\PaymentMethodsSettings', 'updateActivePaymentMethods']
        );

        return
            // Create table orders sequra
            $this->createTable() &&
            $this->addFieldsToProductsTable();
    }

    private function initMiniwidgetConfiguration(string $country): void
    {
        $lang = \Language::getIdByIso($country);
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_TEASER_MSG',
            $this->module->l('Desde %s/mes con seQura', 'installer', $lang)
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CART_TEASER_MSG',
            $this->module->l('Desde %s/mes con seQura', 'installer', $lang)
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_MINICART_TEASER_MSG',
            $this->module->l('Desde %s/mes con seQura', 'installer', $lang)
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_BELOW_MSG',
            $this->module->l('Fracciona con seQura a partir de %s', 'installer', $lang)
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CART_BELOW_MSG',
            $this->module->l('Fracciona con seQura a partir de %s', 'installer', $lang)
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_MINICART_BELOW_MSG',
            $this->module->l('Fracciona con seQura a partir de %s', 'installer', $lang)
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CSS_SEL',
            '.product-prices'
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_CSS_SEL',
            'article.product-miniature div.product-price-and-shipping'
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CATEGORIES_CSS_SEL_PRICE',
            'article.product-miniature [itemprop=price]'
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CART_CSS_SEL',
            'div.cart-summary-line.cart-total span.value'
        );
        self::initConfigurationValue(
            'SEQURA_' . $country . '_PARTPAYMENT_CART_CSS_SEL_PRICE',
            'div.cart-summary-line.cart-total'
        );
    }

    private function registerController($className, $name)
    {
        if (\Tab::getIdFromClassName($className)) {
            return true;
        }

        $tab = new \Tab();
        $tab->active = true;
        $tab->name = [];
        $tab->class_name = $className;
        foreach (\Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        $tab->id_parent = -1;
        $tab->module = $this->module->name;

        return (bool) $tab->add();
    }

    protected function registerHooks()
    {
        $errors = [];
        foreach ($this->_hook_list as $hook) {
            if (!$this->module->registerHook($hook)) {
                $errors[] = $hook;
            }
        }

        return $errors;
    }

    public static function initConfigurationValue($name, $value)
    {
        if (!\Configuration::hasKey($name)) {
            \Configuration::updateGlobalValue($name, $value);
        }
    }

    /**
     * Add Order States to Prestashop
     *
     * @param string $name
     * @param string $title
     * @param string $color
     * @param bool $shipped
     * @param bool $paid
     * @param bool $hidden
     * @param bool $send_email
     *
     * @return void
     */
    public function addOrderState(
        $name,
        $title,
        $color,
        $shipped = false,
        $paid = false,
        $hidden = false,
        $send_email = false,
        $invoice = false,
        $template = null,
        $logable = false
    ) {
        $orderState = new \OrderState((int) \Configuration::get($name));
        if ($orderState->id) {
            return;
        }
        // don't create new if there is alreade one with the same name
        foreach (\OrderState::getOrderStates(\Context::getContext()->language->id) as $existing_state) {
            if ($existing_state['name'] == $name) {
                return;
            }
        }
        $orderState = new \OrderState();
        $orderState->name = [];
        foreach (\Language::getLanguages() as $language) {
            $orderState->name[$language['id_lang']] = $title;
            $orderState->template[$language['id_lang']] = $template;
        }

        $orderState->send_email = $send_email;
        $orderState->invoice = $invoice;
        $orderState->color = $color;
        $orderState->hidden = $hidden;
        $orderState->delivery = false;
        $orderState->logable = true;
        $orderState->unremovable = true;
        $orderState->module_name = $this->module->name;
        $orderState->logable = $logable;
        if ($shipped) {
            $orderState->shipped = true;
        }
        if ($paid) {
            $orderState->paid = true;
        }
        if ($orderState->save()) {
            copy($this->module->getLocalPath() . DIRECTORY_SEPARATOR . 'logo.gif', _PS_ROOT_DIR_ . '/img/os/' . (int) $orderState->id . '.gif');
        }
        self::initConfigurationValue($name, $orderState->id);
    }

    protected function putFirstAmongPaymentMethods()
    {
        $hook_name = 'paymentOptions';
        $sql = 'SELECT id_hook FROM ' . _DB_PREFIX_ . 'hook WHERE name = "' . $hook_name . '"';
        $id_hook = \Db::getInstance()->getValue($sql);
        $sql = 'SELECT id_module FROM ' . _DB_PREFIX_ . 'module WHERE name = "' . $this->module->name . '"';
        $id_module = \Db::getInstance()->getValue($sql);
        $sql = 'SELECT MIN(position) FROM ' . _DB_PREFIX_ . 'hook_module WHERE id_hook = ' . (int) $id_hook;
        $up_from_one = 2 - \Db::getInstance()->getValue($sql);
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'hook_module SET position=position+' . $up_from_one .
            ' WHERE id_hook = ' . (int) $id_hook;
        \Db::getInstance()->execute($sql);
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'hook_module SET position=1 WHERE id_hook = ' . (int) $id_hook .
            ' AND id_module = ' . (int) $id_module;
        \Db::getInstance()->execute($sql);
    }

    protected function setAllowedCarriers()
    {
        $shops = \Shop::getShops(true, null, true);
        $carriers = \Carrier::getCarriers((int) \Context::getContext()->language->id, false, false, false, null, \Carrier::ALL_CARRIERS);
        $carrier_ids = [];
        foreach ($carriers as $carrier) {
            $carrier_ids[] = $carrier['id_reference'];
        }

        foreach ($shops as $s) {
            foreach ($carrier_ids as $id_carrier) {
                if (!\Db::getInstance()->execute('INSERT IGNORE INTO `' . _DB_PREFIX_ . 'module_carrier` (`id_module`, `id_shop`, `id_reference`)
                VALUES (' . (int) $this->module->id . ', "' . (int) $s . '", ' . (int) $id_carrier . ')')) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function createTable()
    {
        $res = (bool) $this->db->execute(
            '
			CREATE TABLE IF NOT EXISTS `' . $this->table_name . '` (
				`order_id` int(10) unsigned NOT NULL,
				`sent_to_sequra` tinyint NOT NULL,
                `merchant_id` varchar(64) NOT NULL,
				PRIMARY KEY (`order_id`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
		'
        );

        return $res && $this->updateTable();
    }

    private function updateTable()
    {
        $results = $this->db->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'sequra_order` LIKE \'merchant_id\''
        );
        if (!$results) {
            return (bool) $this->db->execute(
                'ALTER TABLE `' . _DB_PREFIX_ . 'sequra_order`
                ADD `merchant_id` varchar(64) NOT NULL;'
            );
        }

        return true;
    }

    protected function addFieldToProductsTable($field, $attribute_string)
    {
        $results = $this->db->executeS('SHOW COLUMNS FROM  `' . _DB_PREFIX_ . 'product` where field="' . $field . '";');
        if (count($results) > 0) {
            return true;
        }
        $res = (bool) $this->db->execute(
            'ALTER TABLE `' . _DB_PREFIX_ . 'product` ADD ' .
                $field . ' ' . $attribute_string . ';'
        );

        return $res;
    }

    protected function addFieldsToProductsTable()
    {
        return $this->addFieldToProductsTable('sequra_is_service', 'BOOLEAN NOT NULL DEFAULT TRUE') &&
            $this->addFieldToProductsTable('sequra_is_banned', 'BOOLEAN NOT NULL DEFAULT FALSE') &&
            $this->addFieldToProductsTable('sequra_service_end_date', 'VARCHAR(16) NULL') &&
            $this->addFieldToProductsTable('sequra_desired_first_charge_date', 'VARCHAR(16) NULL') &&
            $this->addFieldToProductsTable('sequra_registration_amount', 'decimal(20,6) NULL DEFAULT 0');
    }

    public function uninstall()
    {
        $this->unregisterHooks();

        if (!$this->deleteTable()) {
            return false;
        }
        // TODO: delete cron job
        return true;
    }

    protected function unregisterHooks()
    {
        $errors = [];
        foreach ($this->_hook_list as $hook) {
            if (!$this->module->unregisterHook($hook)) {
                $errors[] = $hook;
            }
        }

        return $errors;
    }

    protected function deleteTable()
    {
        $this->createTable(); // to avoid errors if it doesn't exist
        $count = (int) $this->db->getValue('select count(*) from ' . $this->table_name);
        if ($count == 0) {
            $this->db->execute('DROP TABLE ' . $this->table_name);
        }

        return true;
    }

    protected function onepagecheckoutpsSetUp()
    {
        $module = \Module::getInstanceByName('onepagecheckoutps');
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'configuration set value = concat(value,\',' . $this->module->name . '\') WHERE name = \'OPC_MODULES_WITHOUT_POPUP\'';
        \Db::getInstance()->execute($sql);
        $sql = 'SELECT id_module FROM ' . _DB_PREFIX_ . 'module WHERE name = "' . $this->module->name . '"';
        $id_module = \Db::getInstance()->getValue($sql);
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'opc_payment (id_module, name_image, force_display) values (' . $id_module . ',\'' . $this->module->name . '.png\',0)';

        if (version_compare($module->version, '2.2', '<')) {
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'opc_payment (id_module, name, force_display) values (' . $id_module . ',\'' . $this->module->name . '.png\',0)';
        }

        \Db::getInstance()->execute($sql);
        copy(
            _PS_MODULE_DIR_ . $this->module->name . '/logo.png',
            _PS_MODULE_DIR_ .
                'onepagecheckoutps/views/img/payments/' . $this->module->name . '.png'
        );
    }
}
