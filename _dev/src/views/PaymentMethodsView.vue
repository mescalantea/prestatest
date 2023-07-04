<script setup>
import { useConfigStore } from '@/store/configStore'
import { usePaymentMethodsStore } from '@/store/paymentMethodsStore'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const config = useConfigStore()
const paymentMethods = usePaymentMethodsStore()
const countries = config.SEQURA_COUNTRIES.split(',').filter (country => config['valid_credentials_' + country])

</script>
<template>
  <MainPanel>
    <template v-slot:title>
      <h1>{{ t('views.payment_methods.title') }}</h1>
    </template>
    <template v-slot:main>
        <v-expansion-panels v-for="country in countries" :key="country" variant="popup">
          <v-expansion-panel :title="country">
            <template v-slot:text>
              <v-table>
                <tbody>
                  <tr v-for="(pm, index) in paymentMethods[country]">
                    <td>
                      <img v-if="pm.icon.startsWith('http')" :src="pm.icon" />
                      <div v-else v-html="pm.icon" />
                    </td>
                    <td>{{ pm.title }}</td>
                    <td>{{ pm.claim }}</td>
                    <td>{{ pm.product }}</td>
                  </tr>
                </tbody>
              </v-table>
            </template>
          </v-expansion-panel>
        </v-expansion-panels>
    </template>
  </MainPanel>
</template>

<script>
// @ is an alias to /src
import MainPanel from '@/components/MainPanel.vue'

export default {
  name: 'PaymentMethodsView',
  beforeRouteEnter() {
    usePaymentMethodsStore().getPaymentMethods()
  },
  components: {
    MainPanel
  },
}
</script>
