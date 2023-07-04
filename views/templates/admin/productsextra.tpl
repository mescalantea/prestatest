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
<style>
	input[pattern]:invalid {
		background-color: #eab3b7;
	}
</style>
<div class="form-group">
	<label id="sequra_is_banned_label" for="sequra_is_banned" class="control-label col-lg-5">
		<span>
			{l s='Prevent this product from being paid by SeQura?:' mod='sequra'}
		</span>
		<span class="help-box" data-toggle="popover"
			data-content="{l s='There are some kind of product that can not be sold with SeQura, like live animal, weapons or inlegal stuff.' mod='sequra'}">
		</span>
	</label>
	<div class="col-lg-9">
			<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="sequra_is_banned"
								 id="sequra_is_banned_on" value="1" {if $sequra_is_banned} checked="checked"{/if}/>
					<label for="sequra_is_banned_on">{l s='Yes' d='Admin.Global'}</label>
					<input type="radio" name="sequra_is_banned"
								 id="sequra_is_banned_off" value="0" {if !$sequra_is_banned} checked="checked"{/if}/>
					<label for="sequra_is_banned_off">{l s='No' d='Admin.Global'}</label>
					<a class="slide-button btn"></a>
			</span>
	</div>
</div>
{if $sequra_for_services}
<div class="form-group">
	<label id="sequra_is_service_label" for="sequra_is_service" class="control-label col-lg-3">
			<span class="label-tooltip" data-toggle="tooltip"
						title="{l s='Sequra should deal with this product as a service' mod='sequra'}">
				{l s='Is service?:' mod='sequra'}
			</span>
	</label>
	<div class="col-lg-9">
			<span class="switch prestashop-switch fixed-width-lg">
					<input onclick="toggleSequraServiceEndDate(true)" type="radio" name="sequra_is_service"
								 id="sequra_is_service_on" value="1" {if $sequra_is_service} checked="checked"{/if}/>
					<label for="sequra_is_service_on">{l s='Yes' d='Admin.Global'}</label>
					<input onclick="toggleSequraServiceEndDate(false)" type="radio" name="sequra_is_service"
								 id="sequra_is_service_off" value="0" {if !$sequra_is_service} checked="checked"{/if}/>
					<label for="sequra_is_service_off">{l s='No' d='Admin.Global'}</label>
					<a class="slide-button btn"></a>
			</span>
	</div>
</div>
<div class="form-group" id="sequra_service_end_date_row">
	<label class="control-label col-lg-3" for="sequra_is_service">
		<span class="label-tooltip" data-toggle="tooltip" title="{l s='Service end date or period' mod='sequra'}">
			{l s='Service end date or period' mod='sequra'}:
		</span>
	</label>
	<div class="col-lg-2">
		<input type="text"
					 id="sequra_service_end_date"
					 class="form-control fixed-width-lg"
					 name="sequra_service_end_date"
					 value="{$sequra_service_end_date|htmlentitiesUTF8|default:''}"
					 placeholder="Formato ISO8601"
					 pattern="{$ISO8601_PATTERN}"
		/>
	</div>
	<div class="col-lg-9">
		<ol>
			<li>
				<strong>{l s='Date or term in which the course will be given by finished or the service by borrowed' mod='sequra'}</strong>
			</li>
			<li>{l s='Example: Date as 2017-08-31, period as P3M15D (3 months and 15 days)' mod='sequra'}
			<li>{l s='Leave empty if the product is not a course or service' mod='sequra'}</li>
		</ol>
		</p>
	</div>
</div>
<script>
    function toggleSequraServiceEndDate(show) {
        if (show) {
            $('#sequra_service_end_date_row').show();
            $('#sequra_service_end_date').disabled = false;
        } else {
            $('#sequra_service_end_date_row').hide();
            $('#sequra_service_end_date').disabled = true;
        }
    }

    toggleSequraServiceEndDate({$sequra_is_service});
</script>
{/if}
{if $sequra_allow_payment_delay}
<div class="form-group" id="sequra_desired_first_charge_date_row">
	<label class="control-label col-lg-3" for="sequra_is_service">
		<span>
			{l s='First instalment delay or date' mod='sequra'}:
		</span>
		<span class="help-box" data-toggle="popover"
			data-title="{l s='Date or period in which the first instalment will be charged' mod='sequra'}"
			data-content="{l s='Example: Date as 2017-08-31, period as P3M15D (3 months and 15 days)' mod='sequra'}">
		</span>
	</label>
	<div class="col-lg-2">
		<input type="text"
					 id="sequra_desired_first_charge_date"
					 class="form-control fixed-width-lg"
					 name="sequra_desired_first_charge_date"
					 value="{$sequra_desired_first_charge_date|htmlentitiesUTF8|default:''}"
					 placeholder="Formato ISO8601"
					 pattern="{$ISO8601_PATTERN}"
		/>
	</div>
</div>
{/if}
{if $sequra_allow_registration_items}
<div class="form-group" id="sequra_registration_amount_row">
	<label class="control-label col-lg-5" for="sequra_is_service">
			<span class="label-tooltip" data-toggle="tooltip" title="{l s='Registration amount' mod='sequra'}">
				{l s='Registration amount' mod='sequra'}:
			</span>
			<span class="help-box" data-toggle="popover"
				data-content="{l s='Part of the amount of the product that will be paid in advance' mod='sequra'}">
			</span>
	</label>
	<div class="col-lg-2">
		<div class="input-group money-type">
				<input type="text" id="sequra_registration_amount" name="sequra_registration_amount" data-display-price-precision="6" class="form-control" value="{$sequra_registration_amount}">
              <div class="input-group-append">
                <span class="input-group-text"> €</span>
            </div>
    	</div>
	</div>
</div>
{/if}