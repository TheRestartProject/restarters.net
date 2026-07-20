<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useAuthStore } from '~/stores/auth.js'
import { useAuth } from '~/composables/useAuth.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'
import IconLogo from './icons/IconLogo.vue'
import IconTalk from './icons/IconTalk.vue'
import IconFixometer from './icons/IconFixometer.vue'
import IconEvents from './icons/IconEvents.vue'
import IconGroups from './icons/IconGroups.vue'
import IconWiki from './icons/IconWiki.vue'
import IconAdmin from './icons/IconAdmin.vue'
import AppNotifications from './AppNotifications.vue'

// Driven entirely by the session store (design.md §6.2 task brief): a null
// session user renders login/join links, otherwise role-conditional items
// (admin menu for Administrators, network-coordinator link) plus the user
// dropdown and the notifications badges. Talk/Wiki route through the SSO
// bridge (design.md §4.3, useSsoBridge) on click - the plain href stays as
// a fallback for open-in-new-tab/right-click, but lands unauthenticated
// there since a fresh ticket can only be minted on click.
const { t } = useI18n()
const sessionStore = useSessionStore()
const authStore = useAuthStore()
const { hasRole } = useAuth()
const { goTo } = useSsoBridge()

const user = computed(() => sessionStore.user)
const config = computed(() => sessionStore.config || {})

// Bound (not static) so Vite's SFC asset-URL transform doesn't try to
// resolve it as a build-time module import - it's a runtime request for a
// public/ asset (design.md's Stylesheet-stage note: /images/* assets land
// in client/public/images/ in a later asset-migration stage).
const avatarSrc = '/images/placeholder-avatar.webp'

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

// Mirrors legacy navbar.blade.php's Talk link:
// `${DISCOURSE_URL}/session/sso?return_path=${DISCOURSE_URL}`.
function goToTalk() {
  const base = config.value.discourse_url
  if (!base) {
    return
  }

  goTo(`${base}/session/sso?return_path=${encodeURIComponent(base)}`)
}

function goToWiki() {
  const base = config.value.wiki_url
  if (!base) {
    return
  }

  goTo(base)
}
</script>

<template>
  <nav class="nav-wrapper ps-0 pe-0" data-testid="app-navbar">
    <NuxtLink to="/" class="icon-brand" data-testid="nav-logo">
      <IconLogo />
    </NuxtLink>

    <ul id="nav-left" class="nav-left d-flex justify-content-between w-100 pe-md-3">
      <li>
        <a :href="config.discourse_url || '#'" rel="noopener noreferrer" data-testid="nav-talk" @click.prevent="goToTalk">
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
        <a :href="config.wiki_url || '#'" rel="noopener noreferrer" data-testid="nav-wiki" @click.prevent="goToWiki">
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
        <li data-testid="nav-notifications">
          <AppNotifications />
        </li>

        <li class="nav-item dropdown">
          <button
            id="navbarDropdown"
            type="button"
            class="nav-link dropdown-toggle"
            aria-haspopup="true"
            :aria-expanded="menuOpen"
            :aria-label="t('client.nav.toggle_account_nav')"
            data-testid="nav-user-menu"
            @click="toggleMenu"
          >
            <img :src="avatarSrc" :alt="t('client.nav.avatar_alt', { name: user.name })" class="avatar">
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
                  <li>
                    <NuxtLink to="/admin/preview-deploy" data-testid="nav-admin-preview-deploy" @click="closeMenu">
                      Preview deploy
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
