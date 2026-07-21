<script setup>
import { computed, onMounted, ref } from 'vue'
import { useToastStore } from '~/stores/toast.js'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useSessionStore } from '~/stores/session.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import GroupActions from '~/components/groups/GroupActions.vue'
import GroupStats from '~/components/groups/GroupStats.vue'
import GroupDevicesSummary from '~/components/groups/GroupDevicesSummary.vue'
import GroupDevicesBreakdown from '~/components/groups/GroupDevicesBreakdown.vue'
import GroupVolunteers from '~/components/groups/GroupVolunteers.vue'
import GroupEventsList from '~/components/groups/GroupEventsList.vue'
import GroupInviteModal from '~/components/groups/GroupInviteModal.vue'
import GroupShareStatsModal from '~/components/groups/GroupShareStatsModal.vue'
import StatsShareImageModal from '~/components/events/StatsShareImageModal.vue'
import GroupCollapsibleSection from '~/components/groups/GroupCollapsibleSection.vue'

// /group/view/[id] - resources/views/group/view.blade.php +
// resources/js/components/GroupPage.vue (+ GroupHeading/GroupActions/
// GroupDescription/GroupVolunteers/GroupStats/GroupEvents/
// GroupDevicesWorkedOn/GroupDevicesMostRepaired/GroupDevicesBreakdown) are
// the functional spec (design.md §6.2 B5 task brief; parity-v2 findings
// group-view.md's 16 gaps).
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const groupsStore = useGroupsStore()
const sessionStore = useSessionStore()

const id = computed(() => Number(route.params.id))

const group = computed(() => groupsStore.current.data)
const permissions = computed(() => group.value?.permissions || {})
const canedit = computed(() => !!permissions.value.can_edit)
const candemote = computed(() => !!permissions.value.can_demote)
// The "delete" control actually archives (DELETE /api/v2/groups/{id} ->
// archivev2, reversible); relabelled to "Archive" and gated on
// can_perform_archive (Administrator OR NetworkCoordinator-of-group), matching
// group/edit/[id].vue and legacy GroupActions.vue. There genuinely is no
// hard-delete route wired up server-side (routes/api.php's groups DELETE
// always hits GroupMembershipController::archivev2) even though the
// can_see_delete/can_perform_delete permission flags exist in the API
// response - so unlike develop's disabled "Delete group" item, there's
// nothing for a "Delete group" menu item to call.
const canPerformArchive = computed(() => !!permissions.value.can_perform_archive)
const isMember = computed(() => groupsStore.isMember(id.value))
// GroupEvents.vue's showCalendar: Auth::check() && (isVolunteer OR
// Administrator) - approximated here with the flags already on hand.
const showCalendar = computed(() => isMember.value || canedit.value || canPerformArchive.value)

const { uploadedImageUrl } = useUploadedImageUrl()
const groupImage = computed(() => uploadedImageUrl(group.value?.image) || '/images/placeholder-avatar.webp')
const location = computed(() => {
  const loc = group.value?.location
  if (!loc) return null
  return typeof loc === 'string' ? loc : loc.location
})

// GroupDescription.vue's discourseGroup (view.blade.php: $group->discourse_group
// ? DISCOURSE_URL.'/g/'.$group->discourse_group : null). The v2 Group
// resource doesn't carry discourse_group yet, so this stays null (the link
// simply doesn't render) until that field is added server-side.
const discourseGroup = computed(() => {
  const slug = group.value?.discourse_group
  const base = sessionStore.config?.discourse_url
  return slug && base ? `${base}/g/${slug}` : null
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

// StatsImpact.vue's :count="Math.round(stats.co2_total)" passed to
// StatsShareModal - the CO2e total the canvas image's headline/visualisation
// are built from.
const shareImageCount = computed(() => Math.round(groupsStore.stats.data?.group_stats?.co2_total ?? 0))

const truncatedDescription = computed(() => {
  if (!showReadMore.value) return strippedDescription.value
  return strippedDescription.value.substring(0, DESCRIPTION_LIMIT) + '...'
})

const showInvite = ref(false)
// GroupShareStatsModal (the header's "Share group stats" dropdown item -
// GroupActions.vue's @share-stats) is the embed-code modal. StatsShareImage-
// Modal (GroupStats.vue's own CO2-card "Share this" button - a SEPARATE
// @share-stats emit on a different component) is develop's canvas-painted
// social-image generator (StatsImpact.vue's `share` handler opened
// StatsShareModal, not the embed modal) - two different features that
// happen to share an event name on their respective emitters. Imported from
// components/events/ because the canvas painter/modal pair has no group- or
// event-specific logic (only the `count` prop differs) and was shared
// unchanged between the two pages in develop too.
const showShareStats = ref(false)
const showShareImage = ref(false)
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

// GroupActions.vue's join/leave dropdown items - groupsStore.join/leave
// already handle the optimistic update, revert-on-error and error toast
// (see GroupJoinButton.vue, the same store methods this mirrors).
async function onJoin() {
  await groupsStore.join(id.value).catch(() => {})
}

// GroupPage.vue keeps a `haveLeft` flag and renders a green banner naming
// the group with a link back to it, so leaving is confirmed rather than only
// implied by the button flipping to Join. Set only on success - a failed
// leave already toasts and reverts.
const haveLeft = ref(false)

const unfollowedMessage = computed(() =>
  t('groups.now_unfollowed', { name: group.value?.name ?? '', link: `/group/view/${id.value}` })
)

async function onLeave() {
  try {
    await groupsStore.leave(id.value)
    haveLeft.value = true
  } catch {
    // leave() has already toasted and reverted the optimistic update.
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
    <BAlert
      v-if="haveLeft"
      :model-value="true"
      variant="success"
      dismissible
      data-testid="group-view-unfollowed"
      @closed="haveLeft = false"
    >
      <!-- eslint-disable-next-line vue/no-v-html -->
      <span v-html="unfollowedMessage" />
    </BAlert>

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
      <!-- GroupHeading.vue: 5px top / 1px bottom border bar, image+name+tags
           (left, border-right divider on desktop) | location+website+GROUP
           ACTIONS (right). -->
      <header class="group-header d-flex flex-wrap" data-testid="group-view-header">
        <div class="group-header__left d-flex">
          <img :src="groupImage" alt="" class="groupImage align-self-start me-4 mb-3" data-testid="group-view-image">
          <div>
            <h1 data-testid="group-view-name">{{ group.name }}</h1>
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
          </div>
        </div>

        <div class="group-header__right d-flex flex-wrap justify-content-between flex-grow-1">
          <div>
            <div v-if="location" class="fw-bold" data-testid="group-view-location">{{ location }}</div>
            <a v-if="group.website" :href="group.website" target="_blank" rel="noopener" data-testid="group-view-website">
              {{ t('groups.website') }}
            </a>
          </div>

          <div class="d-flex align-items-start gap-2 flex-wrap">
            <GroupActions
              :group-id="id"
              :canedit="canedit"
              :is-member="isMember"
              :can-perform-archive="canPerformArchive"
              :archived="!!group.archived_at"
              @invite="showInvite = true"
              @share-stats="showShareStats = true"
              @join="onJoin"
              @leave="onLeave"
              @archive="askArchive"
            />
          </div>
        </div>

        <template v-if="canPerformArchive && confirmingArchive">
          <div class="w-100 d-flex align-items-center gap-2 mt-2">
            <span class="small">{{ t('groups.archive_group_confirm', { name: group.name }) }}</span>
            <BButton
              variant="danger"
              :disabled="archiving"
              data-testid="group-view-archive-confirm"
              @click="confirmArchive"
            >
              {{ t('partials.yes') }}
            </BButton>
            <BButton variant="link" @click="cancelArchive">{{ t('partials.cancel') }}</BButton>
          </div>
        </template>
      </header>

      <!-- About (left) | Volunteers (right) two-column, matching the live site. -->
      <div class="d-flex flex-wrap mb-4">
        <section class="w-100 w-md-50 pe-md-4 mb-3 mb-md-0" data-testid="group-view-description">
          <GroupCollapsibleSection>
            <template #title>
              <h2 class="mb-0">{{ t('groups.about') }}</h2>
            </template>

            <!-- GroupDescription.vue's badges-row: the archived badge lives here,
                 not the header - tags stay in the header per GroupHeading.vue. -->
            <div v-if="group.archived_at" class="mb-2">
              <BBadge variant="secondary" pill data-testid="group-view-archived">
                {{ t('groups.archived_group') }}
              </BBadge>
            </div>

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
            <!-- GroupDescription.vue: "View group conversation" Discourse link,
                 shown once the group has a linked discourse_group. -->
            <p v-if="discourseGroup" class="d-flex align-items-center gap-2" data-testid="group-view-talk">
              <img :src="'/icons/talk_ico.svg'" alt="" width="30" height="30" class="flex-shrink-0">
              <a :href="discourseGroup">{{ t('groups.talk_group') }}</a>
            </p>
          </GroupCollapsibleSection>
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

      <!-- GroupStatsFacts + StatsImpact only here; GroupPage.vue puts Events
           immediately after, then devices-worked-on/most-repaired/breakdown
           further down (parity gap 13). -->
      <GroupStats
        :stats="groupsStore.stats.data"
        :loading="groupsStore.stats.loading"
        :error="!!groupsStore.stats.error"
        @share-stats="showShareImage = true"
      />

      <hr>

      <GroupEventsList
        :events="groupsStore.events.data"
        :loading="groupsStore.events.loading"
        :group-id="id"
        :group-name="group.name"
        :canedit="canedit"
        :show-calendar="showCalendar"
      />

      <div class="d-flex flex-wrap pt-4">
        <GroupDevicesSummary :stats="groupsStore.stats.data" class="w-100" />
      </div>

      <GroupDevicesBreakdown :cluster-stats="groupsStore.stats.data?.cluster_stats" />

      <GroupInviteModal :show="showInvite" :group-id="id" :shareable-link="group?.shareable_link || ''" @close="showInvite = false" />
      <GroupShareStatsModal
        :show="showShareStats"
        :group-id="id"
        :group-name="group.name"
        @close="showShareStats = false"
      />
      <StatsShareImageModal
        :show="showShareImage"
        :count="shareImageCount"
        @close="showShareImage = false"
      />
    </template>
  </div>
</template>

<style scoped>
/* Match develop's GroupHeading.vue: the group heading image is restricted to
   67px wide (height auto) instead of rendering at its full uploaded size. */
/* GroupHeading.vue:12 - `align-self-start` on the image itself. Without it
   the flex row's default `align-items: stretch` stretches the image to the
   row height, so a portrait logo renders tall and narrow. */
.groupImage {
  width: 67px;
  height: auto;
}

/* GroupHeading.vue's border-top-very-thick/border-bottom-thin: a 5px top /
   1px bottom black border bar wrapping the whole header row. */
.group-header {
  border-top: 5px solid #000;
  border-bottom: 1px solid #000;
  padding: 1.5rem 0;
  margin-bottom: 1.5rem;
}

/* GroupHeading.vue:11,22 - `w-xs-100 w-md-50` on BOTH columns, so each is
   capped at half the row at md+. Without the cap the left column sizes to its
   content, and a long group name pushes the actions column onto its own line
   instead of wrapping within its half. min-width:0 lets a flex item shrink
   below its content width at all, which is what actually allows the wrap. */
.group-header__left {
  padding-right: 1.5rem;
  width: 100%;
  min-width: 0;
}

.group-header__left h1 {
  overflow-wrap: anywhere;
}

@media (min-width: 768px) {
  .group-header__left,
  .group-header__right {
    width: 50%;
  }
}

/* GroupHeading.vue's .bord: a vertical divider between the image+name column
   and the location/actions column, desktop only. */
@media (min-width: 768px) {
  .group-header__left {
    border-right: 1px solid #000;
  }
}

.group-header__right {
  padding-left: 1.5rem;
  min-width: 0;
  align-items: center;
}
</style>
