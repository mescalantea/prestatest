{*
* 2015 Prestaworks AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to info@prestaworks.se so we can send you a copy immediately.
*
*  @author    Prestaworks AB <info@prestaworks.se>
*  @copyright 2015 Prestaworks AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Prestaworks AB
*}
<script>
(function(){
	var button = document.createElement("button"),
		onclick = document.createAttribute("onlcick"),
		text = document.createTextNode("{l s='Request payment with seQura' mod='sequra'}");
	onclick = "";
	button.setAttributeNode(onclick);
	button.appendChild(text);
	document.querySelector('#formAddPaymentPanel .panel-heading').appendChild(button)
}())
</script>
