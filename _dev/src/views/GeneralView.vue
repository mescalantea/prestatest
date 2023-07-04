<script setup>
import { useConfigStore } from '@/store/configStore'
import { storeToRefs } from 'pinia'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const {
  SEQURA_FORCE_NEW_PAGE,
  SEQURA_SEND_CANCELLATIONS,
  SEQURA_AUTOCRON
} = storeToRefs(useConfigStore())
const config = useConfigStore()

const order_id_field_items = [
  { value: '0', title: t('views.general.reference_option_0') },
  { value: '1', title: t('views.general.reference_option_1') },
]
const order_id_field_selected = ref(
  {
    value: config.SEQURA_ORDER_ID_FIELD,
    title: order_id_field_items.find(item => item.value === config.SEQURA_ORDER_ID_FIELD).title
  }
)

const order_status_items = order_statuses.map(os => { return { value: os.id_order_state, title: os.name } })
let selected_status = order_status_items.find(item => item.value == config.SEQURA_OS_NEEDS_REVIEW)
const order_os_need_review_selected = ref(
  {
    value: config.SEQURA_OS_NEEDS_REVIEW,
    title: selected_status?selected_status.title: ""
  }
)

selected_status = order_status_items.find(item => item.value == config.SEQURA_OS_APPROVED)
const order_os_approved_selected = ref(
  {
    value: config.SEQURA_OS_APPROVED,
    title: selected_status?selected_status.title: ""
  }
)

selected_status = order_status_items.find(item => item.value == config.SEQURA_OS_CANCELED)
const order_os_canceled_selected = ref(
  {
    value: config.SEQURA_OS_CANCELED,
    title: selected_status?selected_status.title: ""
  }
)

selected_status = order_status_items.find(item => item.value == config.SEQURA_PS_CANCELED)
const order_ps_canceled_selected = ref(
  {
    value: config.SEQURA_PS_CANCELED,
    title: selected_status?selected_status.title: ""
  }
)
</script>
<template>
  <MainPanel>
    <template v-slot:title>
      <h1>{{ $t('views.general.title') }}</h1>
    </template>
    <template v-slot:main>
      <v-container>
        <v-row>
          <v-col>
            <v-switch id="SEQURA_FORCE_NEW_PAGE" v-model="SEQURA_FORCE_NEW_PAGE"
              :label="$t('views.general.force_new_page_label')" true-value="1" false-value="0" color="primary"
              :hint="$t('views.general.force_new_page_help')" @change="event => config.persist(event.target.id)" inset>
            </v-switch>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-select id="SEQURA_ORDER_ID_FIELD" v-model="order_id_field_selected" :items="order_id_field_items"
              :label="$t('views.general.reference_label')" :hint="$t('views.general.reference_help')" variant="outlined"
              @update:modelValue="value => config.updateConfigWithId('SEQURA_ORDER_ID_FIELD', value)">
            </v-select>
          </v-col>
          <v-col>
            <v-text-field id="SEQURA_BANNED_CAT_IDS" :model-value="config.SEQURA_BANNED_CAT_IDS"
              :label="$t('views.general.exclude_categories_label')"
              :hint="$t('views.general.exclude_categories_help', { ip: browser_ip })" variant="outlined"
              @change="config.updateConfigValue">
            </v-text-field>
          </v-col>
        </v-row>
      </v-container>
      <v-expansion-panels variant="popup">
        <v-expansion-panel :title="$t('views.general.status_configuration')">
          <template v-slot:text>
            <v-container>
              <v-row>
                <v-col>
                  <v-select id="SEQURA_OS_NEEDS_REVIEW" v-model="order_os_need_review_selected"
                    :items="order_status_items" :label="$t('views.general.in_review_status_label')"
                    :hint="$t('views.general.in_review_status_help')" variant="outlined"
                    @update:modelValue="value => config.updateConfigWithId('SEQURA_OS_NEEDS_REVIEW', value)">
                  </v-select>
                </v-col>
              </v-row>
              <v-row>
                <v-col>
                  <v-select id="SEQURA_OS_APPROVED" v-model="order_os_approved_selected" :items="order_status_items"
                    :label="$t('views.general.approved_status_label')" :hint="$t('views.general.approved_status_help')"
                    variant="outlined"
                    @update:modelValue="value => config.updateConfigWithId('SEQURA_OS_APPROVED', value)">
                  </v-select>
                </v-col>
              </v-row>
              <v-row>
                <v-col>
                  <v-select id="SEQURA_OS_CANCELED" v-model="order_os_canceled_selected" :items="order_status_items"
                    :label="$t('views.general.canceled_status_label')" :hint="$t('views.general.canceled_status_help')"
                    variant="outlined"
                    @update:modelValue="value => config.updateConfigWithId('SEQURA_OS_CANCELED', value)">
                  </v-select>
                </v-col>
              </v-row>
            </v-container>
          </template>
        </v-expansion-panel>
        <v-expansion-panel :title="$t('views.general.cancellation_synchronization')">
          <template v-slot:text>
            <v-container>
              <v-row>
                <v-col>
                  <v-switch id="SEQURA_SEND_CANCELLATIONS" v-model="SEQURA_SEND_CANCELLATIONS"
                    :label="$t('views.general.inform_cancellation_label')" true-value="1" false-value="0"
                    @change="event => config.persist(event.target.id)" color="primary"
                    :hint="$t('views.general.inform_cancellation_help')" inset>
                  </v-switch>
                </v-col>
              </v-row>
              <v-scale-transition>
                <v-row v-if="SEQURA_SEND_CANCELLATIONS == '1'">
                  <v-col>
                    <v-select id="SEQURA_PS_CANCELED" v-model="order_ps_canceled_selected" :items="order_status_items"
                      :label="$t('views.general.cancel_order_status_label')"
                      :hint="$t('views.general.cancel_order_status_help')" variant="outlined"
                      @update:modelValue="value => config.updateConfigWithId('SEQURA_PS_CANCELED', value)">
                    </v-select>
                  </v-col>
                </v-row>
              </v-scale-transition>
            </v-container>
          </template>
        </v-expansion-panel>
        <v-expansion-panel :title="$t('views.general.shipping_reports')">
          <template v-slot:text>
            <v-container>
              <v-row>
                <v-col>
                  <v-switch id="SEQURA_AUTOCRON" v-model="SEQURA_AUTOCRON"
                    :label="$t('views.general.automatic_shipping_label')" true-value="1" false-value="0"
                    @change="event => config.persist(event.target.id)" color="primary"
                    :hint="$t('views.general.automatic_shipping_help')" inset>
                  </v-switch>
                </v-col>
              </v-row>
              <v-scale-transition>
                <v-row v-if="SEQURA_AUTOCRON == '1'">
                  <v-col>
                    <v-text-field type="number" id="SEQURA_AUTOCRON_H"
                      :model-value="config.SEQURA_AUTOCRON_H"
                      :rules="[
                        value => (2 <= value && value <= 7) || $t('views.general.shipping_hour_error', { value: 5 })
                      ]" 
                      min="2" max="7" step="1"
                      :label="$t('views.general.shipping_hour_label')"
                      variant="outlined"
                      @change="config.updateConfigValue">
                        <template v-slot:append-inner>AM</template>
                    </v-text-field>
                  </v-col>
                  <v-col>
                    <v-text-field type="number" id="SEQURA_AUTOCRON_M"
                      :model-value="config.SEQURA_AUTOCRON_M"
                      :rules="[
                        value => (0 <= value && value <= 59) || $t('views.general.shipping_minute_error', { value: 59 })
                      ]"
                      min="0" max="59" step="1"
                      :label="$t('views.general.shipping_minute_label')"
                      variant="outlined"
                      @change="config.updateConfigValue">
                    </v-text-field>
                  </v-col>
                </v-row>
              </v-scale-transition>
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
  components: {
    MainPanel
  },
}
</script>
