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

class ProductExtra
{
    /**
     * \Product's Id
     *
     * @var int
     */
    protected $id_product;

    /**
     * Module
     *
     * @var \Sequra
     */
    protected $module;

    public function __construct($id_product)
    {
        $this->id_product = (int) $id_product;
    }

    public function getIdProduct()
    {
        return $this->id_product;
    }

    public function setIdProduct(int $id_product)
    {
        $this->$id_product = (int) $id_product;
    }

    public function shouldTreatAsService($id_shop = null)
    {
        if (\Configuration::get('SEQURA_FOR_SERVICES') && $this->getProductIsService()) {
            return $this->getProductServiceEndDate();
        }
        if (!\Configuration::get('SEQURA_FOR_SERVICES') && $this->getProductIsVirtual()) {
            return $this->getProductServiceEndDate();
        }

        return false;
    }

    public function getProductIsBanned()
    {
        $banned_cats = array_filter(
            explode(',', \Configuration::get('SEQURA_BANNED_CAT_IDS')),
            ['\PrestaShop\Module\PrestashopSequra\SequraTools', 'isInt']
        );
        if (count($banned_cats)) {// Product in banned cat
            $sql = 'select count(*) from `' . _DB_PREFIX_ . 'category_product`' .
            ' where id_product=' . (int) $this->id_product .
            ' and id_category in (' . implode(',', $banned_cats) . ')';
            if (\Db::getInstance()->getValue($sql)) {
                return true;
            }
        }
        // Banned product
        $sql = 'SELECT sequra_is_banned FROM  `' . _DB_PREFIX_ .
            'product` where id_product = "' . (int) $this->id_product . '"';

        return \Db::getInstance()->getValue($sql);
    }

    public function getProductIsService()
    {
        $sql = 'SELECT sequra_is_service FROM  `' . _DB_PREFIX_ .
            'product` where id_product = "' . (int) $this->id_product . '"';

        return \Db::getInstance()->getValue($sql);
    }

    public function getProductIsVirtual()
    {
        $product = new \Product((int) $this->id_product);

        return $product->getType() == \Product::PTYPE_VIRTUAL;
    }

    public function getProductServiceEndDate()
    {
        $sql = 'SELECT sequra_service_end_date FROM  `' . _DB_PREFIX_ . 'product` where id_product = "' . (int) $this->id_product . '"';
        $date = \Db::getInstance()->getValue($sql);
        if (preg_match('/' . SequraTools::ISO8601_PATTERN . '/', $date)) {
            return $date;
        }

        return \Configuration::get('SEQURA_FOR_SERVICES_END_DATE');
    }

    public function getProductFirstChargeDate()
    {
        $sql = 'SELECT sequra_desired_first_charge_date FROM  `' . _DB_PREFIX_ . 'product` where id_product = "' . (int) $this->id_product . '"';
        $date = \Db::getInstance()->getValue($sql);
        if (preg_match('/' . SequraTools::ISO8601_PATTERN . '/', $date)) {
            return $date;
        }

        return '';
    }

    public function getProductRegistrationAmount()
    {
        $sql = 'SELECT sequra_registration_amount FROM  `' . _DB_PREFIX_ . 'product` where id_product = "' . (int) $this->id_product . '"';

        return \Db::getInstance()->getValue($sql);
    }

    public function save($module)
    {
        $this->module = $module;

        return $this->storeValue(
            'sequra_is_banned',
            \Tools::getValue('sequra_is_banned', false) ? 1 : 0
        ) &&
        $this->saveServiceData(
            \Tools::getValue('sequra_is_service'),
            \Tools::getValue('sequra_service_end_date')
        ) &&
        $this->saveInstalmentDelayData(
            \Tools::getValue('sequra_desired_first_charge_date', null),
            \Tools::getValue('sequra_registration_amount', 0)
        );
    }

    private function saveServiceData($sequra_is_service, $service_end_date)
    {
        if ($sequra_is_service) {
            $this->storeValue('sequra_is_service', 1);
            if (!$this->setProductServiceEndDate($service_end_date)) {
                $logger = new \PrestaShopLogger();
                $logger->addLog(
                    sprintf(
                        'An error occurred while updating. %s is not valid in a valid ISO8601 format',
                        $service_end_date
                    )
                );
                return false;
            }
        } else {
            return $this->storeValue('sequra_is_service', false);
        }

        return true;
    }

    private function saveInstalmentDelayData($desired_first_charge_date, $registration_amount)
    {
        if ($desired_first_charge_date && !$this->setDesiredFirstChargeDate($desired_first_charge_date)) {
            \Context::getContext()->controller->errors[] =
                sprintf(
                    $this->module->l(
                        'An error occurred while updating. %s is not valid in a valid ISO8601 format'
                    ),
                    $desired_first_charge_date
                );

            return false;
        }

        return $this->storeValue('sequra_registration_amount', $registration_amount);
    }

    private function setProductServiceEndDate($service_end_date)
    {
        if (!preg_match('/' . SequraTools::ISO8601_PATTERN . '/', $service_end_date)) {
            return false;
        }

        return $this->storeValue('sequra_service_end_date', $service_end_date);
    }

    private function setDesiredFirstChargeDate($desired_first_charge_date)
    {
        if (!preg_match('/' . SequraTools::ISO8601_PATTERN . '/', $desired_first_charge_date)) {
            return false;
        }

        return $this->storeValue('sequra_desired_first_charge_date', $desired_first_charge_date);
    }

    private function storeValue($field, $value)
    {
        return \Db::getInstance()->update(
            'product',
            [
                $field => pSQL($value),
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            'id_product = ' . (int) $this->id_product,
            1,
            true
        );
    }
}
