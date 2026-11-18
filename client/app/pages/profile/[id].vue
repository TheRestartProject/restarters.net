<script setup>
import { computed } from 'vue'
import PublicProfileView from '~/components/profile/PublicProfileView.vue'

// /profile/{id} - resources/views/user/profile-new.blade.php via
// UserController::index($id) (design.md §6.2 Phase D task D2). See
// components/profile/PublicProfileView.vue for the fetch/render logic this
// page just supplies an id to.
definePageMeta({ auth: true })

const route = useRoute()

const userId = computed(() => {
  const parsed = Number(route.params.id)
  return Number.isFinite(parsed) ? parsed : null
})
</script>

<template>
  <div class="container py-4" data-testid="profile-view-page">
    <PublicProfileView :user-id="userId" />
  </div>
</template>
