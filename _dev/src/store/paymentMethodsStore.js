import { defineStore } from 'pinia'
import ajax from '@/requests/ajax.js'

export const usePaymentMethodsStore = defineStore('paymentMethodsStore', {
  state: () => {
    return {
        ES: {},
        FR: {},
        IT: {},
        PT: {},
    }
  },
  getters: {
    partPaymentMethods: (state) => {
      return {
        ES: Object.values(state.ES).filter(m => m.family == 'PARTPAYMENT'),
        FR: Object.values(state.FR).filter(m => m.family == 'PARTPAYMENT'),
        IT: Object.values(state.IT).filter(m => m.family == 'PARTPAYMENT'),
        PT: Object.values(state.PT).filter(m => m.family == 'PARTPAYMENT'),
      }
    }
  },
  actions: {
    getPaymentMethods() {
      return ajax({
        action: 'getPaymentMethods'
        }).then(response => {
            Object.assign(this.ES, response.ES)
            Object.assign(this.FR, response.FR)
            Object.assign(this.IT, response.IT)
            Object.assign(this.PT, response.PT)
        })
    },
  }
})