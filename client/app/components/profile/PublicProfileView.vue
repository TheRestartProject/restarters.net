<script setup>
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useUsersStore } from '~/stores/users.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'

// Shared by pages/profile/index.vue (own profile, no id in the URL) and
// pages/profile/[id].vue (design.md §6.2 Phase D task D2;
// resources/views/user/profile-new.blade.php is the functional spec) -
// mirrors ProfileTabs.vue's "orchestrator component behind a thin page"
// shape.
//
// Public within the app: UserController::index has no permission check
// beyond the route's own `auth` middleware (confirmed by reading
// routes/web.php + app/Http/Controllers/UserController.php directly) - any
// logged-in user may view anyone's profile. The "Edit user" link is the
// only gated element, and that's a client-side judgment call mirroring the
// legacy Blade's own: `$user->id == Auth::id() || hasRole(Administrator)`.
//
// GET /api/v2/users/{id} doesn't exist yet - see stores/users.js and
// docs/nuxt-migration/api-gaps.md Phase D for the documented shape this is
// built against. fetchPublicProfile surfaces the failure as a real
// not-found (404) or generic error state, rather than swallowing it.
const props = defineProps({
  userId: {
    type: Number,
    default: null,
  },
})

const { t } = useI18n()
const { user, hasRole } = useAuth()
const usersStore = useUsersStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const profile = computed(() => usersStore.publicProfile.data)
const loading = computed(() => usersStore.publicProfile.loading)
const error = computed(() => usersStore.publicProfile.error)
const notFound = computed(() => error.value?.status === 404)

const isOwnProfile = computed(() => props.userId !== null && props.userId === user.value?.id)
const canEdit = computed(() => isOwnProfile.value || hasRole('Administrator'))

const avatarUrl = computed(() => uploadedImageUrl(profile.value?.avatar_url))

useHead({ title: t('profile.profile') })

function load() {
  if (props.userId === null) {
    return
  }

  usersStore.fetchPublicProfile(props.userId).catch(() => {})
}

onMounted(load)
watch(() => props.userId, load)
</script>

<template>
  <div data-testid="profile-view">
    <BAlert v-if="userId === null" :model-value="true" variant="danger" data-testid="profile-view-invalid">
      {{ t('client.profile.not_found') }}
    </BAlert>

    <div v-else-if="loading" data-testid="profile-view-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="notFound" :model-value="true" variant="danger" data-testid="profile-view-not-found">
      {{ t('client.profile.not_found') }}
    </BAlert>

    <BAlert v-else-if="error" :model-value="true" variant="danger" data-testid="profile-view-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <template v-else-if="profile">
      <div class="row justify-content-center">
        <div class="col-md-6 panel">
          <div class="row row-compressed profile-header">
            <div class="col-3">
              <img
                :src="avatarUrl || '/images/placeholder-avatar.webp'"
                alt=""
                :class="avatarUrl ? 'img-fluid rounded' : 'img-fluid rounded-circle'"
                :data-testid="avatarUrl ? 'profile-view-avatar' : 'profile-view-avatar-placeholder'"
              >
            </div>
            <div class="col-9 d-flex">
              <div class="align-self-center">
                <h3 data-testid="profile-view-name">{{ profile.name }}</h3>
                <p data-testid="profile-view-role-location">
                  {{ profile.role_name }}<template v-if="profile.location">, {{ profile.location }}</template>
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4 d-flex align-items-start">
          <NuxtLink v-if="canEdit" :to="`/profile/edit/${userId}`" class="btn btn-primary ms-auto" data-testid="profile-view-edit-link">
            {{ t('profile.edit_user') }}
          </NuxtLink>
        </div>
      </div>

      <div class="row justify-content-center mt-3">
        <div class="col-sm-12 col-md-4 order-md-2 panel">
          <h4>{{ t('profile.my_skills') }}</h4>
          <ul class="nav flex-column" data-testid="profile-view-skills">
            <li v-for="skill in profile.skills || []" :key="skill.id">{{ skill.name }}</li>
          </ul>
        </div>

        <div class="col-sm-12 col-md-4 order-md-3 panel">
          <h4>{{ t('users.groups') }}</h4>
          <ul v-if="(profile.groups || []).length" class="nav flex-column" data-testid="profile-view-groups">
            <li v-for="group in profile.groups" :key="group.id">
              <NuxtLink :to="`/group/view/${group.id}`">{{ group.name }}</NuxtLink>
            </li>
          </ul>
          <p v-else data-testid="profile-view-no-groups">{{ t('client.profile.no_groups') }}</p>
        </div>

        <div class="col-sm-12 col-md-6 order-md-1 panel">
          <h4>{{ t('profile.biography') }}</h4>
          <p data-testid="profile-view-bio">
            <template v-if="profile.biography">{{ profile.biography }}</template>
            <em v-else>{{ t('profile.no_bio', { name: profile.name }) }}</em>
          </p>
        </div>
      </div>
    </template>
  </div>
</template>
