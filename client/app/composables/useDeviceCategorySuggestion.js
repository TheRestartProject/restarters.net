/**
 * Exact-match port of EventDevice.vue's `suggestedCategory` computed
 * (resources/js/components/EventDevice.vue) - api-contracts-phase-c.md C5
 * is explicit that today's matching is "exact string only ... no fuse.js
 * or fuzzy scoring in the current code" and that a fuzzy upgrade would be a
 * *new* design decision, not a legacy behaviour to preserve - so this is a
 * faithful, non-fuzzy port, not a rewrite. A plain function (not a
 * composable/computed) so it's trivially unit-testable and reusable from
 * both DeviceForm.vue and its tests without mounting anything.
 *
 * Two-pass lookup, `powered`-filtered throughout:
 *  1. Walk every cluster's categories looking for a category whose
 *     (translated) name case-insensitively equals `itemType`. Deliberately
 *     does NOT stop at the first match - the legacy Vue code used
 *     `Array#forEach` (not `some`/`find`), so if more than one category
 *     name matches, the LAST one encountered wins. Preserved exactly, even
 *     though it's almost certainly incidental (category names are meant to
 *     be unique per powered/unpowered set) - the brief calls for exact
 *     behavioural parity, not a "fixed" version.
 *  2. Only if pass 1 found nothing: walk `itemTypes` (GET /api/v2/items)
 *     for the FIRST item whose `type` case-insensitively equals `itemType`
 *     (legacy used `Array#every` + early-return-false, i.e. first match
 *     wins here, unlike pass 1).
 *
 * @param {string|null} itemType   the free-text item-type string typed so far
 * @param {boolean} powered        which half of the category/item-type list to search
 * @param {Array} clusters         [{id, name, categories: [{idcategories, name, powered}]}]
 * @param {Array} itemTypes        [{type, powered, idcategories, categoryname}] (GET /api/v2/items)
 * @param {Function} [translate]   (key) => string - category/cluster `name` fields are
 *                                 translation keys (lang/en/category.php etc - see
 *                                 EventDevicesReadOnly.vue's `t(device.category.name)`
 *                                 precedent) - pass the i18n `t` function so matching
 *                                 happens against the *displayed* text, exactly as
 *                                 legacy's `this.__(c.name)` did. Defaults to identity.
 * @returns {{idcategories:number, categoryname:string, powered:boolean}|null}
 */
export function suggestDeviceCategory({ itemType, powered, clusters, itemTypes, translate }) {
  if (!itemType) return null

  const t = translate || ((s) => s)
  const wantPowered = Boolean(powered)
  const needle = itemType.toLowerCase()

  let ret = null

  for (const cluster of clusters || []) {
    for (const c of cluster.categories || []) {
      const name = t(c.name)
      if (Boolean(c.powered) === wantPowered && name.toLowerCase() === needle) {
        ret = {
          idcategories: c.idcategories,
          categoryname: c.name,
          powered: c.powered,
        }
        // Deliberately no break here - see the doc comment above: the last
        // match wins, matching the legacy forEach.
      }
    }
  }

  if (!ret) {
    for (const item of itemTypes || []) {
      if (Boolean(item.powered) === wantPowered && needle === (item.type || '').toLowerCase()) {
        ret = {
          idcategories: item.idcategories,
          categoryname: item.categoryname,
          powered: item.powered,
        }
        break
      }
    }
  }

  return ret
}
