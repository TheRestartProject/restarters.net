<script setup>
import { computed } from 'vue'
import { useAuth } from '~/composables/useAuth.js'
import PublicProfileView from '~/components/profile/PublicProfileView.vue'

// /profile (own) - resources/views/user/profile-new.blade.php via
// UserController::index($id = null) (design.md §6.2 Phase D task D2). Also
// the target of ProfileTabs.vue's/[[id]].vue's "View profile" link for
// one's own profile (see pages/profile/edit/[[id]].vue's viewProfileUrl).
definePageMeta({ auth: true })

const { user } = useAuth()

const userId = computed(() => user.value?.id ?? null)
</script>

<template>
  <div class="container py-4" data-testid="profile-view-page">
    <PublicProfileView :user-id="userId" />
  </div>
</template>
