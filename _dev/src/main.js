import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import i18n from '@/lib/i18n';

// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import router from './router'

const seQuraTheme = {
  dark: false,
  colors: {
    primary: '#00C2A3',
    secondary: '#1C1C1C',
  },
}
const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'seQuraTheme',
    themes: {
      seQuraTheme,
    },
  }
})

const pinia = createPinia();

createApp(App)
  .use(router)
  .use(pinia)
  .use(vuetify)
  .use(i18n)
  .mount('#app');
