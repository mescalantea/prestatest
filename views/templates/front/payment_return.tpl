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
<div class="sequraconfirmation">
    {if $on_hold}
    <p class="state"><b>{l s='The payment is in review and we will notify you shortly if it approved or not.' mod='sequra'}</b></p>
    {/if}
    <p class="partner">
    {l s='This service is offered jointly with our seQura partner, which will send you the payment instructions at the time the order is sent.' mod='sequra'}
    </p>
</div>