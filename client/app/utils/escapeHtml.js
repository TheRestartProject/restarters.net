// Escape a value that is about to be substituted into a translation string
// which is then rendered with v-html.
//
// The translator does not escape its {placeholder} replacements, while many of
// our strings wrap those placeholders in markup (groups.now_unfollowed puts the
// group name inside an <a>). Escape the VALUES, not the string, so the markup in
// the string survives and a group called `<img onerror=...>` stays text.
export function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}
