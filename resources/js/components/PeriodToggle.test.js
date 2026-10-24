import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
import { mount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import PeriodToggle from './PeriodToggle.vue'

const localVue = createLocalVue()
localVue.use(BootstrapVue)
localVue.mixin(LangMixin)

test('shows the current period as pressed and emits the chosen one', async () => {
  const wrapper = mount(PeriodToggle, { localVue, propsData: { value: 'all' } })

  expect(wrapper.find('.period-all').classes()).toContain('active')
  expect(wrapper.find('.period-last12').classes()).not.toContain('active')

  await wrapper.find('.period-last12').trigger('click')
  expect(wrapper.emitted().input[0]).toEqual(['last_12_months'])

  await wrapper.setProps({ value: 'last_12_months' })
  expect(wrapper.find('.period-last12').classes()).toContain('active')
})
