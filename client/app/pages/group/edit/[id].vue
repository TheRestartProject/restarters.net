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
// No archive control on this page: legacy GroupAddEdit.vue has none either
// (findings/parity-v2/group-forms.md #11) - archiving is only reachable
// from the group VIEW page's "Group Actions" dropdown (GroupActions.vue,
// wired up in group/view/[id].vue). An earlier version of this page added
// one here anyway as a "deliberate UX improvement"; removed per the
// standing parity-with-develop instruction - additions develop lacks are a
// work item, not a decision to keep.
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

// Role ints per app/Role.php: Root(1) has every role, Administrator(2) is
// the only other role that can change a group's network affiliation
// (legacy GroupAddEditPage's canNetwork = Auth::user()->hasRole('Administrator')).
const isAdmin = computed(() => sessionStore.user?.role === 1 || sessionStore.user?.role === 2)

const updatedMessage = ref('')

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
