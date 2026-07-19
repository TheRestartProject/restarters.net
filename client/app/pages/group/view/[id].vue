<script setup>
import { computed, onMounted, ref } from 'vue'
import { useToastStore } from '~/stores/toast.js'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import GroupJoinButton from '~/components/groups/GroupJoinButton.vue'
import GroupStats from '~/components/groups/GroupStats.vue'
import GroupVolunteers from '~/components/groups/GroupVolunteers.vue'
import GroupEventsList from '~/components/groups/GroupEventsList.vue'
import GroupInviteModal from '~/components/groups/GroupInviteModal.vue'

// /group/view/[id] - resources/views/group/view.blade.php +
// resources/js/components/GroupPage.vue (+ GroupHeading/GroupActions/
// GroupDescription/GroupVolunteers/GroupStats/GroupEvents, folded PR #892
// version) are the functional spec (design.md §6.2 B5 task brief).
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const groupsStore = useGroupsStore()

const id = computed(() => Number(route.params.id))

const group = computed(() => groupsStore.current.data)
const permissions = computed(() => group.value?.permissions || {})
const canedit = computed(() => !!permissions.value.can_edit)
const candemote = computed(() => !!permissions.value.can_demote)
// The "delete" control actually archives (DELETE /api/v2/groups/{id} ->
// archivev2, reversible); relabelled to "Archive" and gated on
// can_perform_archive (Administrator OR NetworkCoordinator-of-group), matching
// group/edit/[id].vue and legacy GroupActions.vue. No hard-delete endpoint exists.
const canPerformArchive = computed(() => !!permissions.value.can_perform_archive)
const isMember = computed(() => groupsStore.isMember(id.value))
const canInvite = computed(() => canedit.value || isMember.value)

const { uploadedImageUrl } = useUploadedImageUrl()
const groupImage = computed(() => uploadedImageUrl(group.value?.image) || '/images/placeholder-avatar.webp')
const location = computed(() => {
  const loc = group.value?.location
  if (!loc) return null
  return typeof loc === 'string' ? loc : loc.location
})

// Read-more/read-less toggle for the About description, ported from
// pages/networks/[id].vue's truncatedDescription/showReadMore pattern
// (develop's GroupDescription.vue uses ReadMore.vue with :max-chars="440").
const DESCRIPTION_LIMIT = 440
const showFullDescription = ref(false)

function stripHtml(value) {
  return String(value).replace(/<[^>]*>/g, '')
}

const strippedDescription = computed(() => {
  const description = group.value?.description
  if (!description) return ''
  return stripHtml(description)
})

const showReadMore = computed(() => strippedDescription.value.length > DESCRIPTION_LIMIT)

const truncatedDescription = computed(() => {
  if (!showReadMore.value) return strippedDescription.value
  return strippedDescription.value.substring(0, DESCRIPTION_LIMIT) + '...'
})

const showInvite = ref(false)
const confirmingArchive = ref(false)
const archiving = ref(false)

useHead({ title: computed(() => group.value?.name || t('groups.groups')) })

function retry() {
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
    // deleteGroup hits DELETE /api/v2/groups/{id}, which archives (reversible).
    await groupsStore.deleteGroup(id.value)
    await navigateTo('/group')
  } catch {
    // Store already toasted the error.
  } finally {
    archiving.value = false
  }
}

function load() {
  groupsStore.fetchCurrent(id.value)
  groupsStore.fetchStats(id.value)
  groupsStore.fetchEvents(id.value)
  groupsStore.fetchVolunteers(id.value)
}

onMounted(() => {
  load()

  // The email accept-invite redirector (GET /group/accept-invite/{id}/{hash},
  // kept in Laravel per design §5) lands here with a query flag in place of
  // the old session flash. Toast it once, then strip the param.
  if (route.query.joined === '1') {
    useToastStore().success(t('groups.invite_confirmed'))
  } else if (route.query.invite === 'invalid') {
    useToastStore().error(t('groups.invite_invalid'))
  }
  if (route.query.joined || route.query.invite) {
    const { joined, invite, ...rest } = route.query
    navigateTo({ query: rest }, { replace: true })
  }
})
</script>

<template>
  <div class="container py-4" data-testid="group-view-page">
    <div v-if="groupsStore.current.loading" data-testid="group-view-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="groupsStore.current.error"
      :model-value="true"
      variant="danger"
      data-testid="group-view-error"
    >
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-view-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else-if="group">
      <header class="d-flex flex-wrap justify-content-between mb-4" data-testid="group-view-header">
        <div class="d-flex">
          <img :src="groupImage" alt="" class="groupImage me-3" data-testid="group-view-image">
          <div>
            <h1 data-testid="group-view-name">{{ group.name }}</h1>
            <BBadge v-if="group.archived_at" variant="secondary" pill data-testid="group-view-archived">
              {{ t('groups.archived_group') }}
            </BBadge>
            <div v-if="group.tags && group.tags.length" class="mb-2" data-testid="group-view-tags">
              <BBadge
                v-for="tag in group.tags"
                :key="tag.id"
                variant="info"
                pill
                class="me-1"
                :data-testid="`group-view-tag-${tag.id}`"
              >
                {{ tag.name }}
              </BBadge>
            </div>
            <div v-if="location" class="fw-bold" data-testid="group-view-location">{{ location }}</div>
            <a v-if="group.website" :href="group.website" target="_blank" rel="noopener" data-testid="group-view-website">
              {{ t('groups.website') }}
            </a>
          </div>
        </div>

        <div class="d-flex align-items-start gap-2 flex-wrap">
          <GroupJoinButton :group-id="id" :group-name="group.name" :is-member="isMember" />
          <NuxtLink
            v-if="canedit"
            :to="`/group/edit/${id}`"
            class="btn btn-outline-primary"
            data-testid="group-view-edit"
          >
            {{ t('groups.edit_group') }}
          </NuxtLink>
          <BButton
            v-if="canInvite"
            variant="outline-primary"
            data-testid="group-view-invite"
            @click="showInvite = true"
          >
            {{ t('groups.invite_volunteers') }}
          </BButton>
          <template v-if="canPerformArchive">
            <template v-if="confirmingArchive">
              <span class="small align-self-center">{{ t('groups.archive_group_confirm', { name: group.name }) }}</span>
              <BButton
                variant="danger"
                :disabled="archiving"
                data-testid="group-view-archive-confirm"
                @click="confirmArchive"
              >
                {{ t('partials.yes') }}
              </BButton>
              <BButton variant="link" @click="cancelArchive">{{ t('partials.cancel') }}</BButton>
            </template>
            <BButton
              v-else
              variant="outline-danger"
              data-testid="group-view-archive"
              @click="askArchive"
            >
              {{ t('groups.archive_group') }}
            </BButton>
          </template>
        </div>
      </header>

      <!-- About (left) | Volunteers (right) two-column, matching the live site. -->
      <div class="d-flex flex-wrap mb-4">
        <section class="w-100 w-md-50 pe-md-4 mb-3 mb-md-0" data-testid="group-view-description">
          <h2>{{ t('groups.about') }}</h2>
          <template v-if="group.description">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div v-if="showFullDescription || !showReadMore" v-html="group.description" />
            <p v-else data-testid="group-view-description-truncated">{{ truncatedDescription }}</p>
            <button
              v-if="showReadMore"
              type="button"
              class="btn btn-link p-0"
              data-testid="group-view-description-toggle"
              @click="showFullDescription = !showFullDescription"
            >
              {{ showFullDescription ? t('groups.read_less') : t('groups.read_more') }}
            </button>
          </template>
          <p v-else class="text-muted" data-testid="group-view-description-empty">
            {{ t('groups.about_none') }}
          </p>

          <p v-if="group.phone" class="fw-bold" data-testid="group-view-phone">
            {{ t('groups.field_phone') }}:
            <a :href="`tel:${group.phone}`">{{ group.phone }}</a>
          </p>
          <p v-if="group.email" class="d-flex align-items-center gap-2" data-testid="group-view-email">
            <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="flex-shrink-0">
              <g transform="translate(1.25 5)">
                <path
                  d="M25.175,4H4.325A3.75,3.75,0,0,0,1,7.75v12.5A3.75,3.75,0,0,0,4.75,24h20a3.75,3.75,0,0,0,3.75-3.75V7.75A3.75,3.75,0,0,0,25.175,4ZM24.6,6.5,16,15.1a1.25,1.25,0,0,1-1.762,0l-8.6-8.6ZM26,20.25a1.25,1.25,0,0,1-1.25,1.25h-20A1.25,1.25,0,0,1,3.5,20.25V8.262l8.6,8.6a3.75,3.75,0,0,0,5.3,0l8.6-8.6Z"
                  transform="translate(-1 -4)"
                  fill="#0e1317"
                />
              </g>
            </svg>
            <a :href="`mailto:${group.email}`">{{ group.email }}</a>
          </p>
        </section>

        <div class="w-100 w-md-50">
          <GroupVolunteers
            :group-id="id"
            :volunteers="groupsStore.volunteers.data"
            :loading="groupsStore.volunteers.loading"
            :canedit="canedit"
            :candemote="candemote"
            @invite="showInvite = true"
          />
        </div>
      </div>

      <hr>

      <GroupStats
        :stats="groupsStore.stats.data"
        :loading="groupsStore.stats.loading"
        :error="!!groupsStore.stats.error"
      />

      <hr>

      <GroupEventsList :events="groupsStore.events.data" :loading="groupsStore.events.loading" />

      <GroupInviteModal :show="showInvite" :group-id="id" @close="showInvite = false" />
    </template>
  </div>
</template>

<style scoped>
/* Match develop's GroupHeading.vue: the group heading image is restricted to
   67px wide (height auto) instead of rendering at its full uploaded size. */
.groupImage {
  width: 67px;
  height: auto;
}
</style>
