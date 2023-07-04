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
<link href="{$pathApp|escape:'htmlall':'UTF-8'}" rel=preload as=script>
<link href="https://emoji-css.afeld.me/emoji.css" rel="stylesheet">
<script>
var sequra_messages = {$sequra_messages nofilter};
var sequra_config = {$sequra_config};
var order_statuses = {$order_statuses};
</script>
<div id="app"></div>
<script src="{$chunkVendor|escape:'htmlall':'UTF-8'}"></script>
<script src="{$pathApp|escape:'htmlall':'UTF-8'}"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@3.3.3/dist/vuetify.min.js"></script>
