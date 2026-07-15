<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useAuthStore } from '~/stores/auth.js'
import { useAuth } from '~/composables/useAuth.js'
import IconLogo from './icons/IconLogo.vue'
import IconTalk from './icons/IconTalk.vue'
import IconFixometer from './icons/IconFixometer.vue'
import IconEvents from './icons/IconEvents.vue'
import IconGroups from './icons/IconGroups.vue'
import IconWiki from './icons/IconWiki.vue'
import IconAdmin from './icons/IconAdmin.vue'

// Driven entirely by the session store (design.md §6.2 task brief): a null
// session user renders login/join links, otherwise role-conditional items
// (admin menu for Administrators, network-coordinator link, notifications
// placeholder) plus the user dropdown. Talk/Wiki are plain hrefs to
// config.discourse_url/wiki_url for now - they will route via the SSO
// bridge (design.md §4.3) once that lands.
const { t } = useI18n()
const sessionStore = useSessionStore()
const authStore = useAuthStore()
const { hasRole } = useAuth()

const user = computed(() => sessionStore.user)
const config = computed(() => sessionStore.config || {})

// Bound (not static) so Vite's SFC asset-URL transform doesn't try to
// resolve it as a build-time module import - it's a runtime request for a
// public/ asset (design.md's Stylesheet-stage note: /images/* assets land
// in client/public/images/ in a later asset-migration stage).
const avatarSrc = '/images/placeholder-avatar.png'

const menuOpen = ref(false)

function toggleMenu() {
  menuOpen.value = !menuOpen.value
}

function closeMenu() {
  menuOpen.value = false
}

const isAdministrator = computed(() => hasRole('Administrator'))
const isNetworkCoordinator = computed(() => hasRole('NetworkCoordinator'))

const singleNetwork = computed(() => {
  const networks = user.value?.networks || []
  return networks.length === 1 ? networks[0] : null
})

async function logout() {
  closeMenu()
  await authStore.logout()
  await navigateTo('/login')
}
</script>

<template>
  <nav class="nav-wrapper pl-0 pr-0" data-testid="app-navbar">
    <NuxtLink to="/" class="icon-brand" data-testid="nav-logo">
      <IconLogo />
    </NuxtLink>

    <ul id="nav-left" class="nav-left d-flex justify-content-between w-100 pr-md-3">
      <li>
        <a :href="config.discourse_url || '#'" rel="noopener noreferrer" data-testid="nav-talk">
          <IconTalk />
          <span>{{ t('general.menu_discourse') }}</span>
        </a>
      </li>
      <li>
        <NuxtLink to="/fixometer" data-testid="nav-fixometer">
          <IconFixometer />
          <span>{{ t('general.menu_fixometer') }}</span>
        </NuxtLink>
      </li>
      <li>
        <NuxtLink to="/party" data-testid="nav-events">
          <IconEvents />
          <span>{{ t('general.menu_events') }}</span>
        </NuxtLink>
      </li>
      <li>
        <NuxtLink to="/group" data-testid="nav-groups">
          <IconGroups />
          <span>{{ t('general.menu_groups') }}</span>
        </NuxtLink>
      </li>
      <li>
        <a :href="config.wiki_url || '#'" rel="noopener noreferrer" data-testid="nav-wiki">
          <IconWiki />
          <span>{{ t('general.menu_wiki') }}</span>
        </a>
      </li>
    </ul>

    <ul class="nav-right">
      <template v-if="!user">
        <li>
          <NuxtLink to="/login" class="btn btn-outline-dark" data-testid="nav-login">
            {{ t('login.login_title') }}
          </NuxtLink>
        </li>
        <li>
          <NuxtLink to="/user/register" class="btn btn-dark" data-testid="nav-join">
            {{ t('login.join_title_short') }}
          </NuxtLink>
        </li>
      </template>
      <template v-else>
        <li data-testid="nav-notifications" aria-hidden="true">
          <!-- Placeholder: the real notifications bell/dropdown lands with
               the Talk/notifications migration slice (design.md §6.2). -->
        </li>

        <li class="nav-item dropdown">
          <button
            id="navbarDropdown"
            type="button"
            class="nav-link dropdown-toggle"
            aria-haspopup="true"
            :aria-expanded="menuOpen"
            data-testid="nav-user-menu"
            @click="toggleMenu"
          >
            <img :src="avatarSrc" :alt="user.name" class="avatar">
            <span class="user-name">{{ user.name }}</span>
          </button>

          <div
            id="account-nav"
            class="dropdown-menu navbar-dropdown"
            :class="{ show: menuOpen }"
            aria-labelledby="navbarDropdown"
          >
            <ul>
              <li v-if="isAdministrator" data-testid="nav-admin-menu">
                <span><IconAdmin /> {{ t('client.nav.administrator') }}</span>
                <ul>
                  <li>
                    <NuxtLink to="/brands" data-testid="nav-admin-brands" @click="closeMenu">
                      {{ t('client.nav.brands') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink to="/skills" data-testid="nav-admin-skills" @click="closeMenu">
                      {{ t('client.nav.skills') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink to="/tags" data-testid="nav-admin-tags" @click="closeMenu">
                      {{ t('client.nav.group_tags') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink to="/category" data-testid="nav-admin-categories" @click="closeMenu">
                      {{ t('client.nav.categories') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink to="/user/all" data-testid="nav-admin-users" @click="closeMenu">
                      {{ t('client.nav.users') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink to="/role" data-testid="nav-admin-roles" @click="closeMenu">
                      {{ t('client.nav.roles') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink to="/networks" data-testid="nav-admin-networks" @click="closeMenu">
                      {{ t('networks.general.networks') }}
                    </NuxtLink>
                  </li>
                </ul>
              </li>

              <li v-else-if="isNetworkCoordinator" data-testid="nav-network-coordinator-menu">
                <NuxtLink
                  :to="singleNetwork ? `/networks/${singleNetwork.id}` : '/networks'"
                  data-testid="nav-networks"
                  @click="closeMenu"
                >
                  {{ t('networks.general.networks') }}
                </NuxtLink>
              </li>

              <li>
                <ul>
                  <li>
                    <NuxtLink to="/dashboard" data-testid="nav-dashboard" @click="closeMenu">
                      {{ t('client.nav.dashboard') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink :to="`/profile/edit/${user.id}`" data-testid="nav-profile" @click="closeMenu">
                      {{ t('general.profile') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <NuxtLink
                      :to="`/profile/edit/${user.id}#change-password`"
                      data-testid="nav-change-password"
                      @click="closeMenu"
                    >
                      {{ t('auth.change_password') }}
                    </NuxtLink>
                  </li>
                  <li>
                    <button type="button" class="btn btn-link" data-testid="nav-logout" @click="logout">
                      {{ t('general.logout') }}
                    </button>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </li>
      </template>
    </ul>
  </nav>
</template>
