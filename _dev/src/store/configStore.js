import { defineStore } from 'pinia'
import ajax from '@/requests/ajax.js';
import getGlobal from 'globalthis';
const { sequra_config } = getGlobal();

export const useConfigStore = defineStore('configStore', {
  state: () => {
    sequra_config['isLoading'] = false
    sequra_config['justLoaded'] = false
    sequra_config['successfulLoading'] = false
    return sequra_config
  },
  actions: {
    getConfig() {
      return ajax({
          action: 'getConfig',
          data: {}
      }).then(response => {
          Object.assign(this, response);
      });
    },
    updateConfigWithId(id, value) {
      this[id] = value
      this.persist(id)
    },
    updateConfigValue(event) {
      this[event.target.id] = event.target.value;
      return this.persist(event.target.id);
    },
    persist(key) {
      this.startLoading()
      return new Promise( (resolver) => {
        ajax({
          action: 'updateConfigKey',
          data: {
              key: key,
              value: this[key]
          }
        }).then(
          () => {
            resolver()
            this.finishLoading(true)
          },
          () => {
            this.finishLoading(false)
          }
        );
      });
    },
    setPassword(password) {
      this.startLoading()
      ajax({
          action: 'updateConfigKey',
          data: {
              key: "SEQURA_PASS",
              value: password
          }
      }).then(
        () => {
          this.getConfig()
          this.finishLoading(true)
        },
        () => {
          this.finishLoading(false) 
        }
      )
    },
    startLoading(){
      this['isLoading'] = true;
    },
    finishLoading(successfully){
      this['justLoaded'] = true;
      this['isLoading'] = false;
      setTimeout(() => { this['justLoaded'] = false }, 3000);
      this['successfulLoading'] = successfully
    }
  }
})