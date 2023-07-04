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
var sequraProducts = [];
{foreach from=$sequra_products key=k item=p}
sequraProducts.push("{$p}");
{/foreach}
var sequraConfigParams = {
    merchant: "{$merchant}",
    assetKey: "{$assetKey}",
    products: sequraProducts,
    scriptUri: "{$scriptBaseUri}sequra-checkout.min.js",
    decimalSeparator: '{$decimalSeparator}',
    thousandSeparator: '{$thousandSeparator}',
    locale: '{$locale}',
    currency: 'EUR'
};

{literal}

(function (i, s, o, g, r, a, m) {i['SequraConfiguration'] = g;i['SequraOnLoad'] = [];i[r] = {};i[r][a] = function (callback) {i['SequraOnLoad'].push(callback);};(a = s.createElement(o)), (m = s.getElementsByTagName(o)[0]);a.async = 1;a.src = g.scriptUri;m.parentNode.insertBefore(a, m);})(window, document, 'script', sequraConfigParams, 'Sequra', 'onLoad');

//Helper
var SequraHelper = {
  presets: {
      L:         '{"alignment":"left"}',
      R:         '{"alignment":"right"}',
      legacy:    '{"type":"legacy"}',
      legacyL:  '{"type":"legacy","alignment":"left"}',
      legacyR:  '{"type":"legacy","alignment":"right"}',
      minimal:   '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as"}',
      minimalL: '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as","alignment":"left"}',
      minimalR: '{"type":"text","branding":"none","size":"S","starting-text":"as-low-as","alignment":"right"}'
  },
  drawnWidgets: [],
  getText: function (selector) {
      return  selector && document.querySelector(selector)?document.querySelector(selector).innerText:"0";
  },
  nodeToCents: function (node) {
      return SequraHelper.textToCents( node?node.innerText:"0" );
  },
  selectorToCents: function (selector) {
      return SequraHelper.textToCents(SequraHelper.getText(selector));
  },

  textToCents: function (text) {
      text = text.replace(/^\D*/,'').replace(/\D*$/,'');
      if(text.indexOf(sequraConfigParams.decimalSeparator)<0){
          text += sequraConfigParams.decimalSeparator + '00';
      }
      return SequraHelper.floatToCents(
          parseFloat(
                  text
                  .replace(sequraConfigParams.thousandSeparator,'')
                  .replace(sequraConfigParams.decimalSeparator,'.')
          )
      );
  },

  floatToCents: function (value) {
      return parseInt(value.toFixed(2).replace('.', ''), 10);
  },

  mutationCallback: function(mutationList, mutationObserver) {
      SequraHelper.refreshWidget(mutationList[0].target)
  },

  refreshWidgets: function (price_src) {
      SequraHelper.waitForElememt(price_src).then(function(){
        document.querySelectorAll(price_src).forEach(function(item,index){
            if(!item.getAttribute('observed-by-sequra-promotion-widget')){
                item.setAttribute('observed-by-sequra-promotion-widget',price_src)
            }
            SequraHelper.refreshWidget(item);
        });
      })
  },

  refreshWidget: function (price_item) {
    var new_amount = SequraHelper.textToCents(price_item.innerText)
    document.querySelectorAll('[observes^=\"' + price_item.getAttribute('observed-by-sequra-promotion-widget') + '\"]').forEach(function(item) {
        item.setAttribute('data-amount', new_amount);
    });
    SequraHelper.refreshComponents();
  },

  refreshComponents: function () {
      Sequra.onLoad(
          function(){
              Sequra.refreshComponents();
          }
      );
  },

  drawPromotionWidget: function (price_src,dest,product,theme,reverse,campaign) {
      if(!dest){
          return;
      }
      if(SequraHelper.drawnWidgets[price_src+dest+product+theme+reverse+campaign]){
          return;
      }
	  SequraHelper.drawnWidgets[price_src+dest+product+theme+reverse+campaign] = true;
      var srcNodes = document.querySelectorAll(price_src);
      if(srcNodes.length==0){
          console.error(price_src + ' is not a valid css selector to read the price from, for sequra widget.');
          return;
      }
      destNodes = document.querySelectorAll(dest)
      if(destNodes.length==0){
          console.error(dest + ' is not a valid css selector to write sequra widget to.');
          return;
      }
      destNodes.forEach(function(destNode,i) {
          if(typeof(srcNodes[i])==="undefined"){
              return;
          }
          destNode.setAttribute('price_src',price_src);
          destNode.setAttribute('unique_dest_id',price_src+'_'+i);
          SequraHelper.drawSinglePromotionWidget(srcNodes[i],destNode,product,theme,reverse,campaign);
      });
      this.refreshComponents();
  },

  drawSinglePromotionWidget: function (srcNode,destNode,product,theme,reverse,campaign) {
      var promoWidgetNode = document.createElement('div');
      var price_in_cents = 0;

      var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
      if(MutationObserver && srcNode){//Don't break if not supported in browser
          if(!srcNode.getAttribute('observed-by-sequra-promotion-widget')){//Define only one observer per price_src
              var mo = new MutationObserver(SequraHelper.mutationCallback);
              mo.observe(srcNode, {childList: true, subtree: true});
              mo.observed_as = destNode.getAttribute('price_src');
              srcNode.setAttribute('observed-by-sequra-promotion-widget',destNode.getAttribute('unique_dest_id'));
          }
      }
      promoWidgetNode.setAttribute('observes', destNode.getAttribute('unique_dest_id'));
      price_in_cents = SequraHelper.nodeToCents(srcNode);
      promoWidgetNode.className = 'sequra-promotion-widget';
      promoWidgetNode.setAttribute('data-amount',price_in_cents);
      promoWidgetNode.setAttribute('data-product',product);
      if(this.presets[theme]){
          theme = this.presets[theme]
      }
      try {
          attributes = JSON.parse(theme);
          for (var key in attributes) {
              promoWidgetNode.setAttribute('data-'+key,""+attributes[key]);
          }
      } catch(e){
          promoWidgetNode.setAttribute('data-type','text');
      }
      if(reverse){
          promoWidgetNode.setAttribute('data-reverse',reverse);
      }
      if(campaign){
          promoWidgetNode.setAttribute('data-campaign',campaign);
      }
      if (destNode.nextSibling) {//Insert after
          destNode.parentNode.insertBefore(promoWidgetNode, destNode.nextSibling);
      }
      else {
          destNode.parentNode.appendChild(promoWidgetNode);
      }
  },

  waitForElememt: function (selector) {
    return new Promise( function(resolve) {
        if (document.querySelector(selector)) {
            return resolve();
        }
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (!mutation.addedNodes)
                    return;
                var found = false;
                mutation.addedNodes.forEach(function(node){
                        found = found || (node.matches && node.matches(selector));
                });
                if(found) {
                    resolve();
                    observer.disconnect();
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
  }
};

{/literal}
</script>
