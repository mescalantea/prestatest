<script setup>
import { useConfigStore } from '@/store/configStore'
import { storeToRefs } from 'pinia'
const { isLoading, justLoaded, successfulLoading, SEQURA_MODE } = storeToRefs(useConfigStore())
</script>
<template>
  <v-app id="inspire">
    <v-main class="bg-grey-lighten-3">
      <v-snackbar
          :timeout="2000"
          v-model="justLoaded"
          :color="successfulLoading?'primary':'error'"
          location="top"
        >
          <v-snackbar-text>
            <v-icon color="white" left>{{ successfulLoading?'mdi-check-circle':'mdi-alert-circle' }}</v-icon>
            <span style="padding:1em;color:white" v-if="successfulLoading">{{ $t('views.success_msg') }}</span>
            <span style="padding:1em;color:white" v-else>{{ $t('views.error_msg') }}</span>
          </v-snackbar-text>
      </v-snackbar>
      <v-container>
        <v-row>
          <v-col>
            <v-card rounded="lg">
              <v-card-text>
                <v-container>
                  <v-row>
                    <v-col cols="3" class="text-left">
                      <img alt="seQura logo" src="https://live.sequracdn.com/assets/images/logos/logo.svg" />
                    </v-col>
                    <v-col class="text-center">
                      <slot name="title"></slot>
                      <v-chip :color="SEQURA_MODE=='live'?'primary':'secondary'" style="position: absolute;top: 1em;right: 1em; font-size: 65%; text-transform: uppercase;" >
                        {{ $t('views.setup.enabled_mode', { mode: $t('views.setup.mode.' + SEQURA_MODE) }) }}
                      </v-chip>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="3">
            <v-card rounded="lg">
              <v-card-text>
                <LeftMenu />
              </v-card-text>
            </v-card>
          </v-col>
          <v-col>
            <v-card rounded="lg" style="padding:2rem" :loading="isLoading" :disabled="isLoading">
              <v-card-text>
                <slot name="main"></slot>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>
<script>
import LeftMenu from '@/components/menu/LeftMenu.vue'

export default {
  name: 'MainPanel',
  components: {
    LeftMenu
  },
}
</script>




