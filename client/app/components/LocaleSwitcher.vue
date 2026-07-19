<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'
import { useSessionStore } from '~/stores/session.js'

// en/fr/fr-BE only (nuxt.config.ts i18n.locales - design.md §7); native
// names are not translated (a French speaker still needs to see
// "Français" while browsing in English to find it).
const LOCALES = [
  { code: 'en', native: 'English' },
  { code: 'fr', native: 'Français' },
  { code: 'fr-BE', native: 'Français (Belgique)' },
]

// @nuxtjs/i18n extends the shared vue-i18n composer returned by useI18n()
// with setLocale()/locale (design.md §7) at runtime inside a real Nuxt
// app - `import { useI18n } from 'vue-i18n'` resolves to that same
// extended composer there. In Vitest, components mount against a bare
// vue-i18n instance (tests/setup.ts) with no such extension, so
// locale-*switching* goes through useNuxtApp().$i18n instead - the same
// escape hatch app/plugins/api.ts already uses for its getLocale() -
// which keeps this testable via the existing
// vi.stubGlobal('useNuxtApp', ...) convention. t() still comes from
// vue-i18n directly like every other component.
const { t } = useI18n()
const authStore = useAuthStore()
const sessionStore = useSessionStore()

const nuxtApp = useNuxtApp()

const current = computed(() => nuxtApp.$i18n?.locale?.value ?? 'en')
const currentLabel = computed(() => LOCALES.find((l) => l.code === current.value)?.native ?? current.value)

const open = ref(false)

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
}

async function choose(code) {
  close()

  if (code === current.value) {
    return
  }

  if (nuxtApp.$i18n?.setLocale) {
    await nuxtApp.$i18n.setLocale(code)
  }

  // PATCH /api/v2/session persists the choice to the account (auth
  // required server-side - SessionController@patchSessionv2) - guests
  // just keep the client-side switch + its cookie (nuxt.config.ts
  // detectBrowserLanguage.useCookie).
  if (authStore.loggedIn) {
    try {
      await sessionStore.saveLocale(code)
    } catch {
      // Best-effort: the UI has already switched locale regardless.
    }
  }
}
</script>

<template>
  <div class="locale-switcher" data-testid="locale-switcher">
    <button
      type="button"
      class="locale-switcher__toggle"
      :aria-label="t('client.locale_switcher.label')"
      :aria-expanded="open"
      data-testid="locale-toggle"
      @click="toggle"
    >
      <svg class="locale-switcher__globe" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
      </svg>
      <span>{{ currentLabel }}</span>
      <svg class="locale-switcher__caret" :class="{ 'locale-switcher__caret--open': open }" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
      </svg>
    </button>

    <ul v-if="open" class="locale-switcher__menu" data-testid="locale-menu">
      <li v-for="loc in LOCALES" :key="loc.code">
        <button
          type="button"
          class="locale-switcher__option"
          :class="{ active: loc.code === current }"
          :data-testid="`locale-option-${loc.code}`"
          @click="choose(loc.code)"
        >
          {{ loc.native }}
        </button>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="scss">
.locale-switcher {
  position: relative;

  // Reads as an interactive dropdown (globe + label + caret), matching the
  // legacy footer language selector rather than bare "English" text.
  &__toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: none;
    background: transparent;
    color: inherit;
    padding: 0;
  }

  &__globe {
    flex: 0 0 auto;
  }

  &__caret {
    flex: 0 0 auto;
    transition: transform 0.15s ease;

    &--open {
      transform: rotate(180deg);
    }
  }

  &__menu {
    position: absolute;
    top: 100%;
    right: 0;
    list-style: none;
    margin: 0.5rem 0 0;
    padding: 0.25rem 0;
    min-width: 180px;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    z-index: 20;
  }

  &__option {
    display: block;
    width: 100%;
    text-align: left;
    border: none;
    background: transparent;
    padding: 0.4rem 0.75rem;

    &.active {
      font-weight: bold;
    }

    &:hover {
      background: rgba(0, 0, 0, 0.05);
    }
  }
}
</style>
