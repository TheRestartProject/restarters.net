<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Admin PR-preview-deploy tooling. Replaces the removed Blade
// admin/preview-deploy page (which couldn't authenticate the post-cutover SPA
// admin, who holds a bearer token, not a web session). Drives
// GET/POST /api/v2/admin/preview-deploys (API\PreviewDeployController).
definePageMeta({ auth: true, role: 'Administrator' })
useHead({ title: 'Preview deploy' })

const { t } = useI18n()
const { $api } = useNuxtApp()

const WORKFLOWS_URL = 'https://github.com/TheRestartProject/restarters.net/actions/workflows/preview-deploy.yml'

const prs = ref([])
const error = ref(null)
const loading = ref(true)
const selectedBranch = ref('')
const deploying = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')
const showConfirm = ref(false)

// Blade's admin/preview-deploy.blade.php always offered a "Main branches"
// optgroup (develop/master) ahead of any open-PR branches, so the select is
// never empty/disabled when there are zero open PRs.
const options = computed(() => {
  const groups = [
    {
      label: t('admin.main_branches'),
      options: [
        { value: 'develop', text: 'develop' },
        { value: 'master', text: 'master' },
      ],
    },
  ]
  if (prs.value.length) {
    groups.push({
      label: t('admin.open_pull_requests'),
      options: prs.value.map((p) => ({ value: p.branch, text: `#${p.number} — ${p.title} (${p.branch}, @${p.author})` })),
    })
  }
  return groups
})

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await $api.previewDeploy.list()
    prs.value = data.prs || []
    error.value = data.error
    selectedBranch.value = prs.value.length ? prs.value[0].branch : 'develop'
  } catch {
    error.value = 'Could not load pull requests.'
  } finally {
    loading.value = false
  }
}

function openConfirm() {
  if (!selectedBranch.value) {
    return
  }
  showConfirm.value = true
}

async function deploy() {
  showConfirm.value = false
  if (!selectedBranch.value) {
    return
  }
  deploying.value = true
  feedback.value = ''
  try {
    const { data } = await $api.previewDeploy.deploy(selectedBranch.value)
    feedback.value = data.message
    feedbackVariant.value = 'success'
  } catch (e) {
    feedback.value = e?.body?.message || 'Deploy failed.'
    feedbackVariant.value = 'danger'
  } finally {
    deploying.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="container py-4" data-testid="preview-deploy-page">
    <h1>PR preview deploy</h1>
    <p>Deploy an open pull request's branch to <strong>restarters-dev</strong> (the build takes ~15 minutes).</p>

    <div v-if="loading" class="placeholder-glow" data-testid="preview-deploy-loading">
      <span class="placeholder col-6" style="height: 2.5rem" />
    </div>

    <BAlert v-else-if="error" :model-value="true" variant="warning" data-testid="preview-deploy-error">
      {{ error }}
    </BAlert>

    <template v-else>
      <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" data-testid="preview-deploy-feedback">
        {{ feedback }}
      </BAlert>

      <div class="d-flex flex-wrap gap-2 align-items-end" style="max-width: 720px">
        <BFormSelect
          v-model="selectedBranch"
          :options="options"
          class="flex-grow-1"
          data-testid="preview-deploy-select"
        />
        <BButton
          variant="primary"
          :disabled="deploying || !selectedBranch"
          data-testid="preview-deploy-submit"
          @click="openConfirm"
        >
          {{ deploying ? 'Deploying…' : 'Deploy' }}
        </BButton>
      </div>

      <BModal
        :model-value="showConfirm"
        :title="t('partials.are_you_sure')"
        no-footer
        data-testid="preview-deploy-confirm-modal"
        @hide="showConfirm = false"
      >
        <p>{{ t('admin.preview_deploy_confirm') }}</p>
        <div class="d-flex justify-content-end gap-2">
          <BButton variant="outline-secondary" data-testid="preview-deploy-confirm-cancel" @click="showConfirm = false">
            {{ t('partials.cancel') }}
          </BButton>
          <BButton variant="primary" data-testid="preview-deploy-confirm-ok" @click="deploy">
            Deploy
          </BButton>
        </div>
      </BModal>
    </template>

    <hr class="mt-4">
    <p class="text-muted small">
      <a :href="WORKFLOWS_URL" target="_blank" rel="noopener noreferrer">{{ t('admin.view_workflows') }}</a>
    </p>
  </div>
</template>
