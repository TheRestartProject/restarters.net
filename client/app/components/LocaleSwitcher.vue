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
      {{ currentLabel }}
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

  &__toggle {
    border: none;
    background: transparent;
    color: inherit;
    padding: 0;
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
