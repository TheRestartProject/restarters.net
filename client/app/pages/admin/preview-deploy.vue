<script setup>
import { computed, onMounted, ref } from 'vue'

// Admin PR-preview-deploy tooling. Replaces the removed Blade
// admin/preview-deploy page (which couldn't authenticate the post-cutover SPA
// admin, who holds a bearer token, not a web session). Drives
// GET/POST /api/v2/admin/preview-deploys (API\PreviewDeployController).
definePageMeta({ auth: true, role: 'Administrator' })
useHead({ title: 'Preview deploy' })

const { $api } = useNuxtApp()

const prs = ref([])
const error = ref(null)
const loading = ref(true)
const selectedBranch = ref('')
const deploying = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

const options = computed(() =>
  prs.value.map((p) => ({ value: p.branch, text: `#${p.number} — ${p.title} (${p.branch}, @${p.author})` }))
)

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await $api.previewDeploy.list()
    prs.value = data.prs || []
    error.value = data.error
    if (prs.value.length) {
      selectedBranch.value = prs.value[0].branch
    }
  } catch {
    error.value = 'Could not load pull requests.'
  } finally {
    loading.value = false
  }
}

async function deploy() {
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
          @click="deploy"
        >
          {{ deploying ? 'Deploying…' : 'Deploy' }}
        </BButton>
      </div>
    </template>
  </div>
</template>
