import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PreviewDeployPage from '../../../app/pages/admin/preview-deploy.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// lang/en/admin.php gained these keys alongside this Nuxt work but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched (php artisan translations:export-client)
// - overlay them here so the spec doesn't depend on regenerating it.
const messages = {
  en: {
    ...en,
    ...clientEn,
    admin: {
      ...en.admin,
      main_branches: 'Main branches',
      open_pull_requests: 'Open pull requests',
      preview_deploy_confirm:
        'This will redeploy restarters.dev with the selected branch and restore the overnight database. Continue?',
      branch_to_deploy: 'Branch to deploy',
      preview_deploy_queued: 'Deploys are queued as GitHub Actions runs.',
      view_workflows: 'View running workflows →',
    },
  },
}

const BAlertStub = { template: '<div v-bind="$attrs"><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormSelectStub = {
  props: ['modelValue', 'options'],
  emits: ['update:modelValue'],
  template:
    '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs"><slot /></select>',
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(PreviewDeployPage, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub, BFormSelect: BFormSelectStub },
    },
  })
}

const PR = { number: 42, title: 'Add feature', branch: 'feature-x', author: 'bob' }

function stubApi({ prs = [], deployResponse } = {}) {
  const list = vi.fn().mockResolvedValue({ data: { prs } })
  const deploy = vi.fn().mockResolvedValue({ data: deployResponse || { message: 'Deployment queued.' } })
  vi.stubGlobal('useNuxtApp', () => ({ $api: { previewDeploy: { list, deploy } } }))
  return { list, deploy }
}

describe('pages/admin/preview-deploy', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  // Gap 14: legacy's <title>/H1 read "Deploy Preview Branch" / "Deploy
  // Preview Branch to restarters.dev".
  it('shows the legacy H1 wording', async () => {
    stubApi({ prs: [] })
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('h1').text()).toBe('Deploy Preview Branch to restarters.dev')
  })

  // Gap 15: legacy shows a bold "Branch to deploy" label above the select.
  it('labels the branch select "Branch to deploy"', async () => {
    stubApi({ prs: [] })
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('label[for="preview-deploy-select"]').text()).toBe('Branch to deploy')
  })

  // Gap 25: legacy has a lead-in sentence before the workflow-runs link.
  it('shows the "Deploys are queued..." lead-in before the workflows link', async () => {
    stubApi({ prs: [] })
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.text()).toContain('Deploys are queued as GitHub Actions runs.')
  })

  it('offers a Main branches group (develop/master) and enables Deploy even with zero open PRs', async () => {
    stubApi({ prs: [] })
    const wrapper = mountPage()
    await flushPromises()

    const select = wrapper.findComponent(BFormSelectStub)
    expect(select.props('options')).toEqual([
      {
        label: 'Main branches',
        options: [
          { value: 'develop', text: 'develop' },
          { value: 'master', text: 'master' },
        ],
      },
    ])
    expect(select.props('modelValue')).toBe('develop')
    expect(wrapper.find('[data-testid="preview-deploy-submit"]').attributes('disabled')).toBeUndefined()
  })

  it('adds an Open pull requests group alongside Main branches when PRs exist', async () => {
    stubApi({ prs: [PR] })
    const wrapper = mountPage()
    await flushPromises()

    const groups = wrapper.findComponent(BFormSelectStub).props('options')
    expect(groups[0].label).toBe('Main branches')
    expect(groups[1]).toEqual({
      label: 'Open pull requests',
      options: [{ value: 'feature-x', text: '#42 Add feature (feature-x)' }],
    })
    expect(wrapper.findComponent(BFormSelectStub).props('modelValue')).toBe('feature-x')
  })

  // Gap 23: legacy's Deploy button is gated by a blocking window.confirm(),
  // not a BModal - matched here.
  it('clicking Deploy asks for confirmation with the legacy message before deploying', async () => {
    const { deploy } = stubApi({ prs: [] })
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true)
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="preview-deploy-submit"]').trigger('click')
    await flushPromises()

    expect(confirmSpy).toHaveBeenCalledWith(
      'This will redeploy restarters.dev with the selected branch and restore the overnight database. Continue?'
    )
    expect(deploy).toHaveBeenCalledWith('develop')
    expect(wrapper.find('[data-testid="preview-deploy-feedback"]').exists()).toBe(true)

    confirmSpy.mockRestore()
  })

  it('does not deploy when the confirmation is dismissed', async () => {
    const { deploy } = stubApi({ prs: [] })
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false)
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="preview-deploy-submit"]').trigger('click')

    expect(deploy).not.toHaveBeenCalled()

    confirmSpy.mockRestore()
  })

  it('links to the GitHub Actions workflow run list', async () => {
    stubApi({ prs: [] })
    const wrapper = mountPage()
    await flushPromises()

    const link = wrapper.find('a')
    expect(link.attributes('href')).toBe(
      'https://github.com/TheRestartProject/restarters.net/actions/workflows/preview-deploy.yml'
    )
    expect(link.attributes('target')).toBe('_blank')
    expect(link.text()).toBe('View running workflows →')
  })
})
