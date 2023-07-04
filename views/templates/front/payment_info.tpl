{**
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
 *}
<script>
Sequra.onLoad( function () {
  Sequra.refreshComponents();
});
</script>
<p>
{$payment_method['description'] nofilter}
{if !in_array($payment_method['product'],['fp1'])}
    <span id="sequra_info_link" class="sequra-educational-popup sequra_more_info"
    data-amount="{$total_price*100}" data-product="{$payment_method['product']}" data-campaign="{$payment_method['campaign']}"
    rel="sequra_invoice_popup_checkout" title="Más información"><span class="sequra-more-info">  {l s='+ info' mod='sequra'}</span>
    </span>
{/if}
</p>
