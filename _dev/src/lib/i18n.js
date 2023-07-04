import { createI18n } from 'vue-i18n'
import getGlobal from 'globalthis';

const { full_language_code, sequra_messages } = getGlobal();
export default createI18n({
    legacy: false,
    locale: full_language_code, 
    fallbackLocale: 'en',
    messages: sequra_messages
  })