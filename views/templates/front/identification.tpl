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
{extends file='page.tpl'}
{block name='page_content'}
    {if isset($error)}
        <div style="background-color: #FAE2E3;border: 1px solid #EC9B9B;line-height: 20px;margin: 0 0 10px;padding: 10px 15px;">{$error}</div>{/if}

    {if isset($nbProducts) && $nbProducts <= 0}
        <p class="warning">{l s='Your shopping cart is empty.' mod='sequra'}</p>
    {else}

        {$identity_form nofilter}
        <script type="text/javascript">
            (function () {
                var sequraCallbackFunction = function () {
                    history.go(-1);
                };
                window.SequraFormInstance.setCloseCallback(sequraCallbackFunction);
                window.SequraFormInstance.show();
            })();
        </script>
    {/if}
{/block}