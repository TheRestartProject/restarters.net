<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import IconLogo from './icons/IconLogo.vue'

// The live Blade footer.blade.php is an empty <footer></footer> (its brand
// chrome ships from resources/global/css only) - there is no markup to
// port 1:1. This rebuilds sensible footer chrome from the #main-footer /
// .footer-logo hooks already in assets/css/_footer.scss plus the real
// lang/en/general.php link strings that exist for this content elsewhere
// in the app (help/FAQ/Restart Project), per design.md §6.3.
const { t } = useI18n()
const sessionStore = useSessionStore()

const config = computed(() => sessionStore.config || {})
const year = new Date().getFullYear()
</script>

<template>
  <footer id="main-footer" data-testid="app-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
      <NuxtLink to="/" class="footer-logo" data-testid="footer-logo">
        <IconLogo />
      </NuxtLink>

      <ul class="list-unstyled d-flex flex-wrap mb-0">
        <li class="mr-3">
          <a :href="config.discourse_url || '#'" rel="noopener noreferrer" data-testid="footer-talk">
            {{ t('general.menu_discourse') }}
          </a>
        </li>
        <li class="mr-3">
          <a :href="config.wiki_url || '#'" rel="noopener noreferrer" data-testid="footer-wiki">
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
        <li>
          <a :href="t('general.restartproject_url')" target="_blank" rel="noopener noreferrer" data-testid="footer-restart-project">
            {{ t('general.therestartproject') }}
          </a>
        </li>
      </ul>

      <p class="blue mb-0" data-testid="footer-copyright">
        &copy; {{ year }} {{ t('general.therestartproject') }}
      </p>
    </div>
  </footer>
</template>
