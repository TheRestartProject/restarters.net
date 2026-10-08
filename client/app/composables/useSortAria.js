// aria-sort for sortable table headers.
//
// ARIA only honours aria-sort on an element with the columnheader/rowheader
// role - i.e. the <th> itself. Putting it on the sort <button> inside the
// header, which is what FixometerSortHeader.vue did, means assistive tech
// ignores it: a button's role does not support the attribute. So the column
// looked instrumented and announced nothing.
//
// develop gets this for free from bootstrap-vue's b-table, which also renders
// a visually-hidden "Click to sort ascending" hint - the text that surfaced
// this, by appearing in develop's rendered output and not in ours.
export function sortAriaValue(key, activeKey, desc) {
  if (!key || key !== activeKey) {
    return 'none'
  }

  return desc ? 'descending' : 'ascending'
}

// The hint announced on the control itself: what clicking will do NEXT, not
// the current state - the current state is what aria-sort carries.
export function sortHintKey(key, activeKey, desc) {
  if (key === activeKey && !desc) {
    return 'client.common.sort_descending'
  }

  return 'client.common.sort_ascending'
}
