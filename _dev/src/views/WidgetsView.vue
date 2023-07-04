<script setup>
import { useConfigStore } from '@/store/configStore'
import { usePaymentMethodsStore } from '@/store/paymentMethodsStore'

const config = useConfigStore()
const countries = config.SEQURA_COUNTRIES.split(',').filter(country => config['valid_credentials_' + country])
const paymentMethods = usePaymentMethodsStore()

</script>
<template>
  <MainPanel>
    <template v-slot:title>
      <h1>{{ $t('views.widgets.title') }}</h1>
    </template>
    <template v-slot:main>
      <v-container>
        <v-row>
          <v-col>
            <v-text-field id="SEQURA_CSS_SEL_PRICE" :model-value="config.SEQURA_CSS_SEL_PRICE"
              :label="$t('views.widgets.price_css_selector_label')" :hint="$t('views.widgets.price_css_selector_help')"
              variant="outlined" @change="config.updateConfigValue">
            </v-text-field>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-text-field id="SEQURA_ASSETS_KEY" :model-value="config.SEQURA_ASSETS_KEY"
              :label="$t('views.widgets.assets_key_label')" :hint="$t('views.widgets.assets_key_help')" variant="outlined"
              @change="config.updateConfigValue">
            </v-text-field>
          </v-col>
        </v-row>
      </v-container>
      <v-expansion-panels v-for="country in countries" :key="country" variant="popup">
        <v-expansion-panel :title="country">
          <template v-slot:text>
            <v-container v-for="(pm, index) in paymentMethods[country]" :key="index">
              <v-card v-if="pm.family !== 'CARD'">
                <v-card-title>{{ pm.title }}</v-card-title>
                <v-card-text>
                  <v-container>
                    <v-row>
                      <v-col>
                        <v-text-field
                          :id="'SEQURA_' + pm.product + '_' + (pm.campaign ? pm.campaign + '_' : '') + country + '_CSS_SEL'"
                          :model-value="config['SEQURA_' + pm.product + '_' + (pm.campaign ? pm.campaign + '_' : '') + country + '_CSS_SEL']"
                          :label="$t('views.widgets.css_selector_label')" :hint="$t('views.widgets.css_selector_help')"
                          variant="outlined" @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                    <v-row>
                      <v-col>
                        <v-text-field
                          :id="'SEQURA_' + pm.product + '_' + (pm.campaign ? pm.campaign + '_' : '') + country + '_WIDGET_THEME'"
                          :model-value="config['SEQURA_' + pm.product + '_' + (pm.campaign ? pm.campaign + '_' : '') + country + '_WIDGET_THEME']"
                          :label="$t('views.widgets.theme_label')" :hint="$t('views.widgets.theme_help')"
                          variant="outlined" @change="config.updateConfigValue">
                        </v-text-field>
                      </v-col>
                    </v-row>
                  </v-container>
                </v-card-text>
              </v-card>
            </v-container>
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
