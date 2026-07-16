<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'
import IconLogo from './icons/IconLogo.vue'
import LocaleSwitcher from './LocaleSwitcher.vue'

// The live Blade footer.blade.php is an empty <footer></footer> (its brand
// chrome ships from resources/global/css only) - there is no markup to
// port 1:1. This rebuilds sensible footer chrome from the #main-footer /
// .footer-logo hooks already in assets/css/_footer.scss plus the real
// lang/en/general.php link strings that exist for this content elsewhere
// in the app (help/FAQ/Restart Project), per design.md §6.3.
const { t } = useI18n()
const sessionStore = useSessionStore()
const { goTo } = useSsoBridge()

const config = computed(() => sessionStore.config || {})
const year = new Date().getFullYear()

// Same SSO-bridge treatment as AppNavbar.vue's Talk/Wiki links (design.md
// §4.3) - kept in sync deliberately rather than shared into a composable
// since these are the only two call sites and the two components' href
// fallback/testid wiring already differs slightly.
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
  <footer id="main-footer" data-testid="app-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
      <NuxtLink to="/" class="footer-logo" data-testid="footer-logo">
        <IconLogo />
      </NuxtLink>

      <ul class="list-unstyled d-flex flex-wrap mb-0">
        <li class="mr-3">
          <a :href="config.discourse_url || '#'" rel="noopener noreferrer" data-testid="footer-talk" @click.prevent="goToTalk">
            {{ t('general.menu_discourse') }}
          </a>
        </li>
        <li class="mr-3">
          <a :href="config.wiki_url || '#'" rel="noopener noreferrer" data-testid="footer-wiki" @click.prevent="goToWiki">
            {{ t('general.menu_wiki') }}
          </a>
        </li>
        <li class="mr-3">
          <a :href="t('general.help_feedback_url')" target="_blank" rel="noopener noreferrer" data-testid="footer-help">
            {{ t('general.menu_help_feedback') }}
          </a>
        </li>
        <li class="mr-3">
          <a :href="t('general.faq_url')" target="_blank" rel="noopener noreferrer" data-testid="footer-faq">
            {{ t('general.menu_faq') }}
          </a>
        </li>
        <li class="mr-3">
          <a :href="t('general.restartproject_url')" target="_blank" rel="noopener noreferrer" data-testid="footer-restart-project">
            {{ t('general.therestartproject') }}
          </a>
        </li>
        <li>
          <NuxtLink to="/about/cookie-policy" data-testid="footer-cookie-policy">
            {{ t('client.cookie_policy.title') }}
          </NuxtLink>
        </li>
      </ul>

      <LocaleSwitcher />

      <p class="blue mb-0" data-testid="footer-copyright">
        &copy; {{ year }} {{ t('general.therestartproject') }}
      </p>
    </div>
  </footer>
</template>
