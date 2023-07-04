<script setup>
import { useConfigStore } from '@/store/configStore'
import { storeToRefs } from 'pinia';
import { ref } from 'vue'
import getGlobal from 'globalthis';
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const config = useConfigStore()

const { SEQURA_USER,
  SEQURA_MERCHANT_ID_ES,
  SEQURA_MERCHANT_ID_FR,
  SEQURA_MERCHANT_ID_IT,
  SEQURA_MERCHANT_ID_PT,
  SEQURA_ALLOW_IP,
  SEQURA_COUNTRIES,
  SEQURA_MODE,
  valid_credentials,
  valid_credentials_ES,
  valid_credentials_FR,
  valid_credentials_PT,
  valid_credentials_IT,
} = storeToRefs(useConfigStore())

const { browser_ip } = getGlobal();
const rules = {
  required: value => !!value || t('varlidation_rules.required'),
}
const enabled_countries = ref(SEQURA_COUNTRIES.value.split(','))
const all_countries = ref([
  { id: 'ES', model: SEQURA_MERCHANT_ID_ES, valid_credentials: valid_credentials_ES, icon: 'em em-flag-es' },
  { id: 'FR', model: SEQURA_MERCHANT_ID_FR, valid_credentials: valid_credentials_FR, icon: 'em em-flag-fr' },
  { id: 'IT', model: SEQURA_MERCHANT_ID_IT, valid_credentials: valid_credentials_IT, icon: 'em em-flag-it' },
  { id: 'PT', model: SEQURA_MERCHANT_ID_PT, valid_credentials: valid_credentials_PT, icon: 'em em-flag-pt' },
])

function updateConfigValue(event) {
  config[event.target.id] = event.target.value
  config.persist(event.target.id)
    .then(() => {
      config.getConfig()
    })
}

function updatePassword(event) {
  config.setPassword(event.target.value)
}
</script>
<template>
  <MainPanel>
    <template v-slot:title>
      <h1>{{ t('views.setup.title') }}</h1>
    </template>
    <template v-slot:main>
      <v-container>
        <v-row v-if="!valid_credentials && SEQURA_MODE === 'sandbox'">
          <v-col>
            <v-alert closable type="warning" variant="outlined">
              <v-alert-text v-html="$t('views.setup.invalid_credentials_msg')">
              </v-alert-text>
            </v-alert>
          </v-col>
        </v-row>
        <v-row v-if="!valid_credentials && SEQURA_MODE === 'live'">
          <v-col>
            <v-alert closable type="info" variant="outlined">
              <v-alert-text v-html="$t('views.setup.invalid_live_credentials_msg')">
              </v-alert-text>
            </v-alert>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-checkbox v-model="SEQURA_MODE" true-value="sandbox" false-value="live"
              :label="$t('views.setup.sandbox_mode_label')" id="SEQURA_MODE" @change="event => updateConfigValue({
                target: {
                  id: event.target.id,
                  value: SEQURA_MODE
                }
              })" color="primary" inset>
            </v-checkbox>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-text-field id="SEQURA_USER" :label="$t('views.setup.username_label')"
              :hint="$t('views.setup.username_help')" variant="outlined" :rules="[rules.required]"
              v-model.trim="SEQURA_USER" @change="updateConfigValue" autocomplete="off">
              <template v-slot:append-inner>
                {{ valid_credentials ? "✅" : "❌" }}
              </template>
            </v-text-field>
          </v-col>
          <v-col v-if="!valid_credentials">
            <v-text-field id="SEQURA_PASS" type="password" :label="$t('views.setup.password_label')"
              :hint="$t('views.setup.password_help')" variant="outlined" autocomplete="off" :rules="[rules.required]"
              @change="updatePassword">
              <template v-slot:append-inner>
                {{ valid_credentials ? "✅" : "❌" }}
              </template>
            </v-text-field>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-text-field id="SEQURA_ALLOW_IP" :label="$t('views.setup.allow_ip_label')"
              :hint="$t('views.setup.allow_ip_help', { ip: browser_ip })" variant="outlined"
              v-model.trim="SEQURA_ALLOW_IP" @change="updateConfigValue">
            </v-text-field>
          </v-col>
          <v-col>
          </v-col>
        </v-row>
        <v-row v-for="(country, index) in all_countries" :key="index">
          <v-scale-transition>
            <v-col v-if="enabled_countries.includes(country.id)">
              <v-text-field :id="`SEQURA_MERCHANT_ID_${country.id}`"
                :label="$t('views.setup.merchant_label', { country: country.id })" variant="outlined"
                v-model.trim="country.model" @change="updateConfigValue">
                <template v-slot:append-inner>
                  <span v-if="country.valid_credentials">✅</span><span v-else>❌</span>
                </template>
              </v-text-field>
            </v-col>
          </v-scale-transition>
          <v-col>
            <v-switch v-model="enabled_countries" @change="event => config.updateConfigValue({
              target: {
                id: 'SEQURA_COUNTRIES',
                value: enabled_countries.join(',')
              }
            })" :value="country.id" color="primary" inset>
              <template v-slot:label>
                <v-icon :icon="country.icon" class="mr-2" />
                <span v-if="enabled_countries.includes(country.id)"> {{ $t('views.enabled') }} </span>
                <span v-else> {{ $t('views.disabled') }} </span>
              </template>
            </v-switch>
          </v-col>
        </v-row>
      </v-container>
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

<style scoped>
.bootstrap .form-control,
.bootstrap input[type=password],
.bootstrap input[type=search],
.bootstrap input[type=text],
.bootstrap select,
.bootstrap textarea {
  border: 0px !important;
}
</style>