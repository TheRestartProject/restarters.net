import { config } from '@vue/test-utils'
import { vi } from 'vitest'

// Nuxt provides these as ambient globals (auto-imported composables/macros)
// inside the real app. Outside of Nuxt's runtime (plain Vitest), source
// files that reference them as bare identifiers need a stand-in - tests
// override these per-suite with vi.stubGlobal() where the return value
// matters; these are just safe no-op defaults so unrelated code doesn't
// blow up on import.
vi.stubGlobal('useRuntimeConfig', () => ({ public: {} }))
vi.stubGlobal('useNuxtApp', () => ({ $api: undefined, $i18n: undefined }))
vi.stubGlobal('defineNuxtPlugin', (fn: unknown) => fn)
vi.stubGlobal('defineNuxtRouteMiddleware', (fn: unknown) => fn)
vi.stubGlobal('navigateTo', vi.fn())
vi.stubGlobal('setPageLayout', vi.fn())

// Page-level macros/composables (design.md §6/§8: pages rely on Nuxt
// macros - definePageMeta, useHead - and router composables; stub safe
// no-op defaults here, individual page specs override useRoute/useRouter
// with vi.stubGlobal() when the query/params matter).
vi.stubGlobal('definePageMeta', (meta: unknown) => meta)
vi.stubGlobal('useHead', () => {})
vi.stubGlobal('useRoute', () => ({ query: {}, params: {}, fullPath: '/' }))
vi.stubGlobal('useRouter', () => ({ push: vi.fn(), replace: vi.fn() }))

// Keep component stub markup terse in snapshots/output.
config.global.renderStubDefaultSlot = true
