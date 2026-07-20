<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useGroupsStore } from '~/stores/groups.js'
import GroupForm from '~/components/groups/GroupForm.vue'

// /group/edit/[id] - resources/views/group/edit.blade.php +
// GroupAddEditPage.vue (design.md §6.2 B6 task brief). Image upload (tus,
// see TusImageUpload.vue) now lives inside GroupForm.vue itself (findings/
// parity-v2/group-forms.md #8 - matches develop's field order, where the
// image picker sits after Description/before Location in both create and
// edit flows, rather than as a separate widget above the whole form).
//
// Archive (permission-gated on can_perform_archive - Administrator or
// NetworkCoordinator-of-the-group, per
// App\Http\Controllers\API\GroupController::groupPermissionsFor) stays on
// this page below the form. This calls DELETE /api/v2/groups/{id} ->
// GroupMembershipController::archivev2, which only ever sets archived_at
// (reversible) - legacy's separate, Administrator-only hard-delete action
// has no surviving API endpoint (routes/api.php has no hard-delete route),
// so it is not reproduced here; see docs/nuxt-migration/findings/
// parity-audit-findings.md "/group/edit/{id} - missing-content".
//
// findings/parity-v2/group-forms.md #11: legacy GroupAddEdit.vue has no
// archive control at all - archiving is only reachable from the group VIEW
// page's "Group Actions" dropdown (GroupActions.vue). Kept here as a
// deliberate UX improvement rather than reverted, per that finding's own
// suggested fix ("if kept, note it as an intentional UX improvement rather
// than parity").
//
// The legacy page's admin-only "Group log" audit tab
// (App\Helpers\Fixometer::hasRole($user,'Administrator') &&
// $group->audits, resources/views/partials/log-accordion.blade.php) is
// deliberately omitted - there is no v2 (or any API) endpoint at all for a
// group's audit trail, only the Blade controller reading $group->audits
// directly. Recorded in docs/nuxt-migration/api-gaps.md B6. The always-
// present "Group details" tab strip is kept (findings/parity-v2/
// group-forms.md #4) so the page's chrome matches even though there's only
// one tab to show; "Group details"/"Group log" are hardcoded English in
// the legacy Blade too (not run through __()), so they're not translated
// here either.
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const sessionStore = useSessionStore()
const groupsStore = useGroupsStore()

const id = computed(() => Number(route.params.id))
const group = computed(() => groupsStore.current.data)
const permissions = computed(() => group.value?.permissions || {})
const canEdit = computed(() => !!permissions.value.can_edit)
const canPerformArchive = computed(() => !!permissions.value.can_perform_archive)

// Role ints per app/Role.php: Root(1) has every role, Administrator(2) is
// the only other role that can change a group's network affiliation
// (legacy GroupAddEditPage's canNetwork = Auth::user()->hasRole('Administrator')).
const isAdmin = computed(() => sessionStore.user?.role === 1 || sessionStore.user?.role === 2)

const updatedMessage = ref('')
const confirmingArchive = ref(false)
const archiving = ref(false)

useHead({ title: computed(() => (group.value ? `${t('groups.editing')} ${group.value.name}` : t('groups.editing'))) })

function load() {
  groupsStore.fetchCurrent(id.value)
}

onMounted(load)

function retry() {
  load()
}

function onUpdated() {
  updatedMessage.value = t('groups.edit_group_save_changes')
  load()
}

function askArchive() {
  confirmingArchive.value = true
}

function cancelArchive() {
  confirmingArchive.value = false
}

async function confirmArchive() {
  confirmingArchive.value = false
  archiving.value = true

  try {
    // groupsStore.deleteGroup archives (see the store's own comment) -
    // kept under that name because it's shared with group/view/[id].vue.
    await groupsStore.deleteGroup(id.value)
    await navigateTo(`/group/view/${id.value}`)
  } catch {
    // Store already toasted the error.
  } finally {
    archiving.value = false
  }
}
</script>

<template>
  <div class="container py-4" data-testid="group-edit-page">
    <div v-if="groupsStore.current.loading" data-testid="group-edit-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="groupsStore.current.error" :model-value="true" variant="danger" data-testid="group-edit-error">
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-edit-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <BAlert v-else-if="group && !canEdit" :model-value="true" variant="danger" data-testid="group-edit-forbidden">
      {{ t('client.groups.edit_forbidden') }}
    </BAlert>

    <div v-else-if="group" class="row justify-content-center">
      <div class="col-lg-12">
        <ul class="nav nav-tabs" data-testid="group-edit-tabs">
          <li class="nav-item">
            <span class="nav-link active">Group details</span>
          </li>
        </ul>

        <!-- resources/sass/_edit.scss's .edit-panel (findings/parity-v2/
             group-forms.md #3): white bg, bordered, hard offset
             drop-shadow, and bold/16px form labels within it. -->
        <div class="group-edit-panel">
          <h1>
            {{ t('groups.editing') }}
            <NuxtLink :to="`/group/view/${id}`" class="headlink" data-testid="group-edit-view-link">{{ group.name }}</NuxtLink>
          </h1>
          <p>{{ t('groups.edit_group_text') }}</p>

          <BAlert v-if="updatedMessage" :model-value="true" variant="success" dismissible data-testid="group-edit-success" @dismissed="updatedMessage = ''">
            {{ updatedMessage }}
          </BAlert>

          <GroupForm
            :group-id="id"
            :initial-group="group"
            :permissions="permissions"
            :is-admin="isAdmin"
            @updated="onUpdated"
          />

          <div v-if="canPerformArchive" class="mt-4 pt-3 border-top">
            <template v-if="confirmingArchive">
              <span class="me-2">{{ t('groups.archive_group_confirm', { name: group.name }) }}</span>
              <BButton variant="danger" :disabled="archiving" data-testid="group-edit-archive-confirm" @click="confirmArchive">
                {{ t('partials.yes') }}
              </BButton>
              <BButton variant="link" @click="cancelArchive">{{ t('partials.cancel') }}</BButton>
            </template>
            <BButton
              v-else
              variant="outline-danger"
              data-testid="group-edit-archive"
              @click="askArchive"
            >
              {{ t('groups.archive_group') }}
            </BButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.group-edit-panel {
  background-color: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px #222;
  padding: 20px;
  margin: 0 0 30px 0;
}

@media (min-width: 992px) {
  .group-edit-panel {
    padding: 30px;
  }
}

.group-edit-panel :deep(label) {
  font-size: 16px;
  font-weight: 700;
}
</style>
