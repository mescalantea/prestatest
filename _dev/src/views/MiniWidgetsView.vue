<script setup>
import { useConfigStore } from '@/store/configStore'
import { usePaymentMethodsStore } from '@/store/paymentMethodsStore'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const config = useConfigStore()
const countries = config.SEQURA_COUNTRIES.split(',').filter (country => config['valid_credentials_' + country])
const paymentMethods = usePaymentMethodsStore()

let part_paymet_product_items = {}
countries.forEach(country => {
  part_paymet_product_items[country] = paymentMethods.partPaymentMethods[country].filter(pm => pm.family === 'PARTPAYMENT')
})

</script>
<template>
  <MainPanel>
    <template v-slot:title>
      <h1>{{ t('views.miniwidgets.title') }}</h1>
    </template>
    <template v-slot:main>
      <v-expansion-panels v-for="country in countries" :key="country" variant="popup">
        <v-expansion-panel :title="country">
          <template v-slot:text>
            <v-select v-if="part_paymet_product_items[country].length > 1"
              :id="'SEQURA_' + country + '_PARTPAYMENT_PRODUCT'" :items="part_paymet_product_items[country]"
              :label="$t('views.miniwidgets.product_label')" :hint="$t('views.miniwidgets.product_help')"
              variant="outlined" :model-value="config['SEQURA_' + country + '_PARTPAYMENT_PRODUCT']"
              @update:modelValue="value => config.updateConfigWithId('SEQURA_' + country + '_PARTPAYMENT_PRODUCT', value)">
            </v-select>
            <v-card :id="`${country}_PARTPAYMENT_CATEGORIES_SHOW`">
              <v-card-text>
                <v-row>
                  <v-col>
                    <v-switch :id="`SEQURA_${country}_PARTPAYMENT_CATEGORIES_SHOW`"
                      :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_SHOW']"
                      :label="$t('views.miniwidgets.categories_show_label')" true-value="1" false-value="0"
                      :hint="$t('views.miniwidgets.categories_show_help')"
                      @update:modelValue="value => config.updateConfigWithId('SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_SHOW', value)"
                      color="primary" inset>
                    </v-switch>
                  </v-col>
                </v-row>
                <v-scale-transition>
                  <v-container v-if="config['SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_SHOW'] == '1'">
                    <v-row>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CATEGORIES_CSS_SEL_PRICE`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_CSS_SEL_PRICE']"
                          :label="$t('views.miniwidgets.price_css_selector_label')"
                          :hint="$t('views.miniwidgets.price_css_selector_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CATEGORIES_CSS_SEL`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_CSS_SEL']"
                          :label="$t('views.miniwidgets.css_selector_label')"
                          :hint="$t('views.miniwidgets.css_selector_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                    <v-row>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CATEGORIES_TEASER_MSG`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_TEASER_MSG']"
                          :label="$t('views.miniwidgets.teaser_msg_label')"
                          :hint="$t('views.miniwidgets.teaser_msg_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CATEGORIES_BELOW_MSG`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CATEGORIES_BELOW_MSG']"
                          :label="$t('views.miniwidgets.below_msg_label')" :hint="$t('views.miniwidgets.below_msg_help')"
                          variant="outlined" @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                  </v-container>
                </v-scale-transition>
              </v-card-text>
            </v-card>
            <v-card :id="`${country}_PARTPAYMENT_CART_SHOW`">
              <v-card-text>
                <v-row>
                  <v-col>
                    <v-switch :id="`SEQURA_${country}_PARTPAYMENT_CART_SHOW`"
                      :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CART_SHOW']"
                      :label="$t('views.miniwidgets.cart_show_label')" true-value="1" false-value="0"
                      :hint="$t('views.miniwidgets.cart_show_help')"
                      @update:modelValue="value => config.updateConfigWithId('SEQURA_' + country + '_PARTPAYMENT_CART_SHOW', value)"
                      color="primary" inset>
                    </v-switch>
                  </v-col>
                </v-row>
                <v-scale-transition>
                  <v-container v-if="config['SEQURA_' + country + '_PARTPAYMENT_CART_SHOW'] == '1'">
                    <v-row>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CART_CSS_SEL_PRICE`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CART_CSS_SEL_PRICE']"
                          :label="$t('views.miniwidgets.price_css_selector_label')"
                          :hint="$t('views.miniwidgets.price_css_selector_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CART_CSS_SEL`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CART_CSS_SEL']"
                          :label="$t('views.miniwidgets.css_selector_label')"
                          :hint="$t('views.miniwidgets.css_selector_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                    <v-row>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CART_TEASER_MSG`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CART_TEASER_MSG']"
                          :label="$t('views.miniwidgets.teaser_msg_label')"
                          :hint="$t('views.miniwidgets.teaser_msg_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_CART_BELOW_MSG`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_CART_BELOW_MSG']"
                          :label="$t('views.miniwidgets.below_msg_label')" :hint="$t('views.miniwidgets.below_msg_help')"
                          variant="outlined" @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                  </v-container>
                </v-scale-transition>
              </v-card-text>
            </v-card>
            <v-card>
              <v-card-text>
                <v-row>
                  <v-col>
                    <v-switch :id="`SEQURA_${country}_PARTPAYMENT_MINICART_SHOW`"
                      :model-value="config['SEQURA_' + country + '_PARTPAYMENT_MINICART_SHOW']"
                      :label="$t('views.miniwidgets.minicart_show_label')" true-value="1" false-value="0"
                      :hint="$t('views.miniwidgets.minicart_show_help')"
                      @update:modelValue="value => config.updateConfigWithId('SEQURA_' + country + '_PARTPAYMENT_MINICART_SHOW', value)"
                      color="primary" inset>
                    </v-switch>
                  </v-col>
                </v-row>
                <v-scale-transition>
                  <v-container v-if="config['SEQURA_' + country + '_PARTPAYMENT_MINICART_SHOW'] == '1'">
                    <v-row>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_MINICART_CSS_SEL_PRICE`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_MINICART_CSS_SEL_PRICE']"
                          :label="$t('views.miniwidgets.price_css_selector_label')"
                          :hint="$t('views.miniwidgets.price_css_selector_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_MINICART_CSS_SEL`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_MINICART_CSS_SEL']"
                          :label="$t('views.miniwidgets.css_selector_label')"
                          :hint="$t('views.miniwidgets.css_selector_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                    <v-row>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_MINICART_TEASER_MSG`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_MINICART_TEASER_MSG']"
                          :label="$t('views.miniwidgets.teaser_msg_label')"
                          :hint="$t('views.miniwidgets.teaser_msg_help')" variant="outlined"
                          @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                      <v-col>
                        <v-text-field :id="`SEQURA_${country}_PARTPAYMENT_MINICART_BELOW_MSG`"
                          :model-value="config['SEQURA_' + country + '_PARTPAYMENT_MINICART_BELOW_MSG']"
                          :label="$t('views.miniwidgets.below_msg_label')" :hint="$t('views.miniwidgets.below_msg_help')"
                          variant="outlined" @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                  </v-container>
                </v-scale-transition>
              </v-card-text>
            </v-card>
          </template>
        </v-expansion-panel>
      </v-expansion-panels>
    </template>
  </MainPanel>
</template>

<script>
import MainPanel from '@/components/MainPanel.vue'

export default {
  name: 'WidgetsView',
  beforeRouteEnter() {
    usePaymentMethodsStore().getPaymentMethods()
  },
  components: {
    MainPanel
  }

}
</script>
