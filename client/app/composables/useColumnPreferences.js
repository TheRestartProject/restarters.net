import { ref } from 'vue'

const STORAGE_PREFIX = 'restarters.columnPreferences.'

/**
 * Small localStorage-backed column-visibility toggle for GroupsTable.vue
 * (plans/active/nuxt-migration.md B4 row: "column_preferences -> user
 * preference API not session").
 *
 * There is no generic user-preferences endpoint to persist this
 * server-side: GET/PATCH /api/v2/users/me/preferences is invites-only
 * (UserController::getMyEmailPreferencesv2/updateMyEmailPreferencesv2), and
 * the legacy `session('column_preferences')` Blade variable it's meant to
 * replace was itself dead code - grepping resources/js turns up no reader
 * for it, so there was never an actual column-visibility feature to port.
 * Recorded in docs/nuxt-migration/api-gaps.md; this composable is a
 * reasonable from-scratch, per-browser reinterpretation until a real
 * preferences slot exists to sync it across devices.
 */
export function useColumnPreferences(key, defaultColumns) {
  const storageKey = STORAGE_PREFIX + key
  const columns = ref({ ...defaultColumns })

  function load() {
    if (typeof localStorage === 'undefined') {
      return
    }

    try {
      const raw = localStorage.getItem(storageKey)
      if (raw) {
        columns.value = { ...defaultColumns, ...JSON.parse(raw) }
      }
    } catch {
      // Corrupt or inaccessible storage - fall back to the defaults.
    }
  }

  function save() {
    if (typeof localStorage === 'undefined') {
      return
    }

    try {
      localStorage.setItem(storageKey, JSON.stringify(columns.value))
    } catch {
      // Storage full/blocked - the preference just won't persist.
    }
  }

  function toggle(columnKey) {
    columns.value = { ...columns.value, [columnKey]: !columns.value[columnKey] }
    save()
  }

  load()

  return { columns, toggle }
}
