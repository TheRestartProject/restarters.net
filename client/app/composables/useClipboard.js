/**
 * Copy-to-clipboard helper, factored out of components/profile/CalendarsTab.vue's
 * inline `copy(text)` so pages/party/index.vue's calendar-link modal doesn't
 * duplicate it. Silently no-ops when the Clipboard API isn't available,
 * matching CalendarsTab.vue's guard.
 */
export function useClipboard() {
  function copy(text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text)
    }
  }

  return { copy }
}
