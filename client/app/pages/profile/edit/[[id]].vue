<script setup>
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useProfileStore } from '~/stores/profile.js'
import ProfileTabs from '~/components/profile/ProfileTabs.vue'

// /profile/edit/[[id]] - resources/views/user/profile-edit.blade.php
// (design.md §6.2 Phase D task D1). The optional `id` mirrors
// UserController::getProfileEdit($id = null): omitted -> the viewer's own
// profile; present -> the target user's, gated the same way the legacy
// controller's 404 guard does (Administrator, Repair Directory Regional/
// Super Admin, or the viewer's own id) - see stores/profile.js and
// ProfileTabs.vue for why the *tabs shown* diverge from the legacy Blade
// page even though the page-level access rule does not.
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const { user, hasRole } = useAuth()
const profileStore = useProfileStore()

const targetId = computed(() => {
  const raw = route.params.id

  if (raw === undefined || raw === null || raw === '') {
    return user.value?.id ?? null
  }

  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : null
})

const isOwnProfile = computed(() => targetId.value !== null && targetId.value === user.value?.id)
const isAdmin = computed(() => hasRole('Administrator'))

const repairDirLoading = computed(() => profileStore.repairDirectory.loading)
const repairDirVisible = computed(() => profileStore.repairDirectoryVisible)

// Only the "neither self nor Administrator" path needs to wait on the
// repair-directory-options fetch to decide access - self/Administrator are
// known synchronously from the session, matching UserController::
// getProfileEdit's guard order.
const checkingAccess = computed(() => !isOwnProfile.value && !isAdmin.value && repairDirLoading.value)
const accessGranted = computed(() => isOwnProfile.value || isAdmin.value || repairDirVisible.value)

const viewProfileUrl = computed(() => (isOwnProfile.value ? '/profile' : `/profile/${targetId.value}`))

// Best-effort "Editing <name>" subheading for the admin-on-someone-else
// case - see stores/profile.js's targetUser doc comment (GET
// /api/v2/users/{id} doesn't exist yet, design.md §6.2 Phase D task D2).
const heading = computed(() => {
  if (isOwnProfile.value || targetId.value === null) {
    return null
  }

  const name = profileStore.targetUser.data?.name
  return name
    ? t('client.profile.editing_user', { name })
    : t('client.profile.editing_user_fallback', { id: targetId.value })
})

useHead({ title: t('profile.page_title') })

function load() {
  if (targetId.value === null) {
    return
  }

  // Needed regardless of isOwnProfile/isAdmin: the Repair Directory tab's
  // visibility (ProfileTabs.vue) depends on it too, not just page access.
  profileStore.fetchRepairDirectoryOptions(targetId.value).catch(() => {})

  if (!isOwnProfile.value) {
    profileStore.fetchTargetUser(targetId.value)
  }
}

onMounted(load)
watch(targetId, load)
</script>

<template>
  <div class="container py-4" data-testid="profile-edit-page">
    <div class="d-flex align-items-center flex-wrap mb-2">
      <h1 class="mb-0 me-3">{{ t('profile.page_title') }}</h1>
      <NuxtLink v-if="targetId !== null" :to="viewProfileUrl" class="btn btn-primary ms-auto" data-testid="profile-edit-view-link">
        {{ isOwnProfile ? t('profile.view_profile') : t('profile.view_user_profile') }}
      </NuxtLink>
    </div>

    <p v-if="heading" class="text-muted" data-testid="profile-edit-heading">{{ heading }}</p>

    <BAlert v-if="targetId === null" :model-value="true" variant="danger" data-testid="profile-edit-invalid">
      {{ t('client.profile.not_found') }}
    </BAlert>

    <div v-else-if="checkingAccess" data-testid="profile-edit-checking-access">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="!accessGranted" :model-value="true" variant="danger" data-testid="profile-edit-forbidden">
      {{ t('client.profile.access_denied') }}
    </BAlert>

    <ProfileTabs v-else :target-id="targetId" :is-own-profile="isOwnProfile" :is-admin="isAdmin" />
  </div>
</template>
