import Vue from "vue";
import 'bootstrap';
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import LangMixin from 'resources/js/mixins/lang.js'

import { mount  } from '@vue/test-utils'
import DeviceWeight from './DeviceWeight.vue'

test('DeviceWeight', () => {
    const wrapper = mount(DeviceWeight, {
        mixins: [LangMixin],
    })

    expect(wrapper.html()).toContain('impact calculation')
})