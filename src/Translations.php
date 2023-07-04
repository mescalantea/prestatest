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

class Translations
{
    /**
     * @var \Module
     */
    private $module = null;

    /**
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * Create all translations (backoffice)
     *
     * @return array<string, array> translation list
     */
    public function getTranslations()
    {
        $locale = \Context::getContext()->language->iso_code;
        $translations[$locale] = [
            'views' => [
                'enable' => $this->module->l('Enable', 'translations'),
                'disable' => $this->module->l('Disable', 'translations'),
                'enabled' => $this->module->l('Enabled', 'translations'),
                'disabled' => $this->module->l('Disabled', 'translations'),
                'yes' => $this->module->l('Yes', 'translations'),
                'no' => $this->module->l('No', 'translations'),
                'error_msg' => $this->module->l('Could\'t update the configuration', 'translations'),
                'success_msg' => $this->module->l('Configuration updated successfully', 'translations'),
                'setup' => [
                    'title' => $this->module->l('Setup', 'translations'),
                    'invalid_credentials_msg' => $this->module->l(
                        'You need to provide valid credentials to proceed with further configuration.
                        <br\>If you don\'t have an account at seQura, you could get sandbox credentials 
                        <a href="https://sequra.es/sign-up-ps">here</a>',
                        'translations',
                        'do_not_scape'
                    ),
                    'invalid_live_credentials_msg' => $this->module->l(
                        'If you have already tested the plugin in sandbox mode and want to start using for 
                        real payment configure the username and password for the live environment
                        <br\>If you don\'t have a live account at seQura, you could ask for one 
                        <a href="https://sqra.es/ask4prod">here</a>',
                        'translations',
                        'do_not_scape'
                    ),
                    'username_label' => $this->module->l('Username', 'translations'),
                    'username_placeholder' => $this->module->l('username', 'translations'),
                    'username_help' => $this->module->l('Your seQura username', 'translations'),

                    'password_label' => $this->module->l('Password', 'translations'),
                    'password_help' => $this->module->l('Password provided by seQura', 'translations'),
                    'sandbox_mode_label' => $this->module->l('Sandbox mode', 'translations'),
                    'enabled_mode' => $this->module->l('{mode} mode enabled', 'translations'),
                    'mode' => [
                        'sandbox' => $this->module->l('Sandbox', 'translations'),
                        'live' => $this->module->l('Live', 'translations'),
                    ],
                    'mode_help' => $this->module->l('Use sandbox credentials to test the module', 'translations'),

                    'merchant_label' => $this->module->l('Merchant ID {country}', 'translations'),
                    'merchant_help' => $this->module->l(
                        'Leave empty if you\'re not 
                        going to operate with seQura in {country}',
                        'translations'
                    ),
                    
                    'allow_ip_label' => $this->module->l('IP addresses allowed', 'translations'),
                    'allow_ip_help' => $this->module->l(
                        'IP of the browser: {ip}. 
                        Leave it empty to show seQura to everyone.',
                        'translations'
                    ),
                ],
                'general' => [
                    'title' => $this->module->l('General settings', 'translations'),
                    'reference_label' => $this->module->l('Identify orders in seQura by', 'translations'),
                    'reference_option_0' => $this->module->l('Order reference', 'translations'),
                    'reference_option_1' => $this->module->l('Order ID', 'translations'),
                    'reference_help' => $this->module->l(
                        'Choose how you want to identify orders in seQura',
                        'translations'
                    ),
                    'force_new_page_label' => $this->module->l('Open SeQura form in a new page', 'translations'),
                    'force_new_page_help' => $this->module->l(
                        'If you have problems with the form, try enabling this option',
                        'translations'
                    ),
                    'exclude_categories_label' => $this->module->l('Exclude categories', 'translations'),
                    'exclude_categories_help' => $this->module->l(
                        'Prevent categories products with indicated IDs to be paid with seQura 
                        (weapons, pornography, living animals, illegal products ...) 
                        List of categories IDS separated by commas. 
                        To deactivate products independently do it from the product detail.',
                        'translations'
                    ),
                    'status_configuration' => $this->module->l('Status configuration', 'translations'),
                    'in_review_status_label' => $this->module->l('In Review', 'translations'),
                    'approved_status_label' => $this->module->l('Approved', 'translations'),
                    'canceled_status_label' => $this->module->l('Canceled', 'translations'),
                    'shipping_reports' => $this->module->l('Shipping reports', 'translations'),

                    'automatic_shipping_label' => $this->module->l('Automatic Report shipping', 'translations'),
                    'automatic_shipping_help' => $this->module->l(
                        'Send delivery report automatically every night',
                        'translations'
                    ),
                    'shipping_hour_label' => $this->module->l('Hour', 'translations'),
                    'shipping_hour_error' => $this->module->l('Hour must be between 2 AM and 7 AM', 'translations'),
                    'shipping_minute_label' => $this->module->l('Minute', 'translations'),
                    'shipping_minute_error' => $this->module->l('Minute must be between 0 and 59', 'translations'),
                    'cancellation_synchronization' => $this->module->l('Synchronize cancellations', 'translations'),
                    'inform_cancellation_label' => $this->module->l(
                        'Inform seQura about cancellations',
                        'translations'
                    ),
                    'cancel_order_status_label' => $this->module->l(
                        'Consider as canceled the orders in status',
                        'translations'
                    ),
                ],
                'payment_methods' => [
                    'title' => $this->module->l('Payment methods', 'translations'),
                ],
                'widgets' => [
                    'title' => $this->module->l('Widget configuration', 'translations'),
                    'price_css_selector_label' => $this->module->l('Price CSS selector', 'translations'),
                    'price_css_selector_help' => $this->module->l(
                        'Product page\'s price css selector used by widgets to read the price from',
                        'translations'
                    ),
                    'assets_key_label' => $this->module->l('Assets key', 'translations'),
                    'assets_key_help' => $this->module->l(
                        'Key to load the widget assets provided by seQura',
                        'translations'
                    ),
                    'css_selector_label' => $this->module->l(
                        'Place CSS selector',
                        'translations'
                    ),
                    'css_selector_help' => $this->module->l(
                        'CSS selector where the widget will be placed in product page',
                        'translations'
                    ),
                    'theme_label' => $this->module->l('Widget visualization params', 'translations'),
                    'theme_help' => $this->module->l(
                        'L, R, minimal, legacy... or params in JSON format ',
                        'translations'
                    ),
                ],
                'miniwidgets' => [
                    'title' => $this->module->l('Mini Widgets configuration', 'translations'),
                    'product_label' => $this->module->l('Product to promote in miniwidgets', 'translations'),
                    'product_help' => $this->module->l(
                        'Which product\'s condition wil appear in the miniwidgets',
                        'translations'
                    ),
                    'categories_show_label' => $this->module->l(
                        'Show installment amount in product listings',
                        'translations'
                    ),
                    'categories_show_help' => $this->module->l(
                        'Once activated a message with the installment amount will 
                        appear in the product listings under each item in the list',
                        'translations'
                    ),
                    'price_css_selector_label' => $this->module->l('Price CSS selector', 'translations'),
                    'price_css_selector_help' => $this->module->l(
                        'Page\'s price css selector used by miniwidgets to read the price from',
                        'translations'
                    ),
                    'css_selector_label' => $this->module->l('Place CSS selector', 'translations'),
                    'css_selector_help' => $this->module->l(
                        'CSS selector where the miniwidget will be placed',
                        'translations'
                    ),
                    'teaser_msg_label' => $this->module->l('Regular message', 'translations', 'translations'),
                    'teaser_msg_help' => $this->module->l('Message to show in the miniwidget', 'translations'),
                    'below_msg_label' => $this->module->l('Message below limit', 'translations'),
                    'below_msg_help' => $this->module->l(
                        'Message to show if the price is below the limit, leave empty to hide',
                        'translations'
                    ),
                    'cart_show_label' => $this->module->l('Show installment amount in cart', 'translations'),
                    'cart_show_help' => $this->module->l(
                        'Once activated a message with the installment amount will appear in the shopping cart page',
                        'translations'
                    ),
                    'minicart_show_label' => $this->module->l(
                        'Show installment amount in cart summary',
                        'translations'
                    ),
                    'minicart_show_help' => $this->module->l(
                        'Once activated a message with the installment amount will appear in the cart summary',
                        'translations'
                    ),
                ],
            ],
            'menu' => [
                'setup' => $this->module->l('Setup', 'translations'),
                'general' => $this->module->l('General', 'translations'),
                'payment_methods' => $this->module->l('Payment Methods', 'translations'),
                'widgets' => $this->module->l('Widgets', 'translations'),
                'miniwidgets' => $this->module->l('Mini Widgets', 'translations'),
                'help' => $this->module->l('Help', 'translations'),
            ],
            'varlidation_rules' => [
                'required' => $this->module->l('Required', 'translations'),
            ],
        ];

        return $translations;
    }
}
