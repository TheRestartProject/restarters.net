<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Admin PR-preview-deploy tooling. Replaces the removed Blade
// admin/preview-deploy page (which couldn't authenticate the post-cutover SPA
// admin, who holds a bearer token, not a web session). Drives
// GET/POST /api/v2/admin/preview-deploys (API\PreviewDeployController).
//
// Gap 23: legacy's Deploy button was a blocking window.confirm() (see
// resources/views/admin/preview-deploy.blade.php's onclick) - matched here
// rather than a BModal, with the same message text (admin.preview_deploy_confirm).
definePageMeta({ auth: true, role: 'Administrator' })
// Gap 14: legacy's <title>/H1 read "Deploy Preview Branch" / "Deploy Preview
// Branch to restarters.dev".
useHead({ title: 'Deploy Preview Branch' })

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
      // Matches legacy's plain "#N Title (branch)" option text.
      options: prs.value.map((p) => ({ value: p.branch, text: `#${p.number} ${p.title} (${p.branch})` })),
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

// Matches legacy's blocking window.confirm() gate before the form submit.
function confirmAndDeploy() {
  if (!selectedBranch.value) {
    return
  }
  if (!window.confirm(t('admin.preview_deploy_confirm'))) {
    return
  }
  deploy()
}

async function deploy() {
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
    <h1>Deploy Preview Branch to restarters.dev</h1>
    <p class="text-muted">
      Select a branch or open PR to deploy to <strong>restarters-dev</strong>. The container will rebuild and restore
      the latest overnight database backup (~15 minutes total).
    </p>

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
        <div class="flex-grow-1">
          <label for="preview-deploy-select" class="form-label"><strong>{{ t('admin.branch_to_deploy') }}</strong></label>
          <BFormSelect
            id="preview-deploy-select"
            v-model="selectedBranch"
            :options="options"
            data-testid="preview-deploy-select"
          />
        </div>
        <BButton
          variant="primary"
          :disabled="deploying || !selectedBranch"
          data-testid="preview-deploy-submit"
          @click="confirmAndDeploy"
        >
          {{ deploying ? 'Deploying…' : 'Deploy' }}
        </BButton>
      </div>
    </template>

    <hr class="mt-4">
    <p class="text-muted small">
      {{ t('admin.preview_deploy_queued') }}
      <a :href="WORKFLOWS_URL" target="_blank" rel="noopener noreferrer">{{ t('admin.view_workflows') }}</a>
    </p>
  </div>
</template>
