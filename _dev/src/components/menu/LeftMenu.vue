<script setup>
import { useConfigStore } from '@/store/configStore'
import { storeToRefs } from 'pinia';
import router from '@/router';
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { valid_credentials } = storeToRefs( useConfigStore() )
const routes = router.options.routes;
</script>

<template>
  <v-list>
    <v-list-item
      v-for="route in routes"
      :key="route.name"
      :to="route.path"
      :disabled="!valid_credentials && route.path !== '/'"
    >
      <v-list-item-content>
        <v-list-item-title>{{ t('menu.' + route.name) }}</v-list-item-title>
      </v-list-item-content>
    </v-list-item>
  </v-list>
</template>
<style scoped>
.bootstrap #app a,
.bootstrap #app a:focus,
.bootstrap #app a:hover {
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
    text-decoration: none;
}
</style>

