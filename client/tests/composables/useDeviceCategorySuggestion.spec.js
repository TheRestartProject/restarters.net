import { describe, expect, it } from 'vitest'
import { suggestDeviceCategory } from '../../app/composables/useDeviceCategorySuggestion.js'

// Exact-match port of EventDevice.vue's `suggestedCategory` computed
// (resources/js/components/EventDevice.vue) - api-contracts-phase-c.md C5:
// "today's matching is exact string only, still backed by GET
// /api/v2/items". These cases port the legacy behaviour, including its
// (undocumented, incidental) "last cluster match wins" quirk.
const CLUSTERS = [
  {
    id: 1,
    name: 'Cluster A',
    categories: [
      { idcategories: 10, name: 'Toaster', powered: true },
      { idcategories: 11, name: 'Kettle', powered: true },
    ],
  },
  {
    id: 2,
    name: 'Cluster B',
    categories: [
      { idcategories: 20, name: 'Bicycle', powered: false },
      // A second category also named "Toaster" (contrived, but exercises
      // the "last match wins" quirk below) - same name, different id,
      // still powered.
      { idcategories: 21, name: 'Toaster', powered: true },
    ],
  },
]

const ITEM_TYPES = [
  { type: 'Blender', powered: true, idcategories: 30, categoryname: 'Kitchen' },
  { type: 'Sofa', powered: false, idcategories: 31, categoryname: 'Furniture' },
  // A duplicate "Blender" entry to exercise "first item-type match wins"
  // (unlike the cluster pass, which takes the last match).
  { type: 'Blender', powered: true, idcategories: 32, categoryname: 'Kitchen (duplicate)' },
]

describe('composables/useDeviceCategorySuggestion', () => {
  it('returns null when itemType is empty', () => {
    expect(suggestDeviceCategory({ itemType: '', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })).toBeNull()
    expect(suggestDeviceCategory({ itemType: null, powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })).toBeNull()
  })

  it('matches a cluster category name case-insensitively', () => {
    const result = suggestDeviceCategory({ itemType: 'kettle', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(result).toEqual({ idcategories: 11, categoryname: 'Kettle', powered: true })
  })

  it('filters cluster matches by the powered flag', () => {
    const result = suggestDeviceCategory({ itemType: 'Bicycle', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(result).toBeNull()

    const unpoweredResult = suggestDeviceCategory({ itemType: 'Bicycle', powered: false, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(unpoweredResult).toEqual({ idcategories: 20, categoryname: 'Bicycle', powered: false })
  })

  it('takes the LAST matching cluster category when more than one name matches (exact port of the legacy forEach, not a fixed version)', () => {
    const result = suggestDeviceCategory({ itemType: 'Toaster', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    // Both idcategories 10 and 21 are named "Toaster" and powered - 21 is
    // encountered later (cluster 2 iterates after cluster 1), so it wins.
    expect(result).toEqual({ idcategories: 21, categoryname: 'Toaster', powered: true })
  })

  it('falls back to itemTypes only when no cluster category matches, taking the FIRST item-type match', () => {
    const result = suggestDeviceCategory({ itemType: 'Blender', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(result).toEqual({ idcategories: 30, categoryname: 'Kitchen', powered: true })
  })

  it('filters itemTypes matches by the powered flag', () => {
    const result = suggestDeviceCategory({ itemType: 'Sofa', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(result).toBeNull()

    const unpoweredResult = suggestDeviceCategory({ itemType: 'Sofa', powered: false, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(unpoweredResult).toEqual({ idcategories: 31, categoryname: 'Furniture', powered: false })
  })

  it('returns null when nothing matches at all', () => {
    const result = suggestDeviceCategory({ itemType: 'Xylophone', powered: true, clusters: CLUSTERS, itemTypes: ITEM_TYPES })
    expect(result).toBeNull()
  })

  it('passes each cluster/category name through the supplied translate function before comparing', () => {
    const translatedClusters = [
      { id: 1, name: 'cluster.key', categories: [{ idcategories: 99, name: 'category.key', powered: true }] },
    ]
    const translate = (key) => ({ 'category.key': 'Drone' })[key] || key

    const result = suggestDeviceCategory({
      itemType: 'Drone',
      powered: true,
      clusters: translatedClusters,
      itemTypes: [],
      translate,
    })

    expect(result).toEqual({ idcategories: 99, categoryname: 'category.key', powered: true })
  })

  it('handles missing clusters/itemTypes arrays gracefully', () => {
    expect(suggestDeviceCategory({ itemType: 'Kettle', powered: true })).toBeNull()
  })
})
