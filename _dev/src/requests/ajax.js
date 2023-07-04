import axios from 'axios';
import { forEach } from 'lodash';

export default function ajax(params) {
  const form = new FormData();
  form.append('ajax', true);
  form.append('action', params.action);
  form.append('controller', 'AdminAjaxSequra');

  forEach(params.data, (value, key) => {
    form.append(key, value);
  });

  return axios
    .post(window.sequra_config_endpoint , form)
    .then(res => res.data)
    .catch(error => {
      throw error;
    });
}
