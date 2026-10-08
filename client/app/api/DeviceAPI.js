import BaseAPI from './BaseAPI.js'

/**
 * Device CRUD + supporting metadata lookups (api-contracts-phase-c.md C5;
 * design.md §6.2 C5 task brief). Device CRUD already exists server-side
 * (API\DeviceController::createDevicev2/updateDevicev2/deleteDevicev2) -
 * read validateDeviceParams() for the exact payload shape this mirrors
 * (eventid/category/item_type/brand/model/age/estimate/problem/notes/
 * repair_status/next_steps/spare_parts/barrier). Both create and update
 * require `eventid` in the body (not just the route for update), and both
 * return a bare {id, device, stats} (not {data: ...}) - same convention as
 * EventAPI.create/update.
 */
export default class DeviceAPI extends BaseAPI {
  create(payload) {
    return this.$post('/api/v2/devices', payload)
  }

  update(id, payload) {
    return this.$patch(`/api/v2/devices/${id}`, payload)
  }

  del(id) {
    return this.$del(`/api/v2/devices/${id}`)
  }

  // GET /api/v2/devices/options (api-contracts-phase-c.md C5, NEW) -
  // {data: {barriers: [{id, name}], spare_parts: [...], next_steps: [...]}}.
  // Item-type suggestions and brands are deliberately NOT duplicated here -
  // see itemTypes()/brands() below, both reusing existing endpoints per the
  // contract's explicit "reuse, don't duplicate" instruction.
  options() {
    return this.$get('/api/v2/devices/options')
  }

  // GET /api/v2/items (EXISTS) - {type, powered, idcategories,
  // categoryname}[]. The entire data source for the item-type autocomplete
  // and the category-suggestion port
  // (composables/useDeviceCategorySuggestion.js).
  itemTypes() {
    return this.$get('/api/v2/items')
  }

  // GET /api/v2/categories (EXISTS) - flat Category[] with cluster/
  // cluster_name joined per row. Grouped client-side into the
  // {id, name, categories[]} shape DeviceForm's category <select> needs
  // (contract C5: "no endpoint returns that shape ... group client-side").
  categories() {
    return this.$get('/api/v2/categories')
  }

  // GET /api/v2/category-clusters (EXISTS) - {id, name}[], ordered.
  categoryClusters() {
    return this.$get('/api/v2/category-clusters')
  }

  // GET /api/v2/brands (EXISTS) - {id, brand_name}[].
  brands() {
    return this.$get('/api/v2/brands')
  }

  // POST /api/v2/devices/{id}/images {upload_key} (api-contracts-phase-c.md
  // C1f) -> {data:{image_url}}. Requires an existing device id - see
  // docs/nuxt-migration/api-gaps.md Phase C for why photo upload is
  // edit-only, not available while adding a new device (mirrors C4's event
  // photo tab being edit-only for the same reason).
  uploadImage(id, uploadKey) {
    return this.$post(`/api/v2/devices/${id}/images`, { upload_key: uploadKey })
  }

  // DELETE /api/v2/devices/{id}/images/{idxref} - the route param is the
  // Xref row id (the device Image resource's `idxref` field), NOT its own
  // `id` field: deleteImagev2 queries Xref::where('idxref', $idimages).
  deleteImage(id, idxref) {
    return this.$del(`/api/v2/devices/${id}/images/${idxref}`)
  }

  // GET /api/v2/devices (api-contracts-phase-c.md C6a, NEW, paginated +
  // filtered) -> {data:{items, count}}. v2 equivalent of
  // GET /api/devices/{page}/{size} (ApiController::getDevices) - read that
  // for the exact query builder this mirrors. Same param names as the
  // legacy FixometerRecordsTable.vue's items() method: page/size/sortBy/
  // sortDesc/powered/category/brand/model/item_type/status/comments/wiki/
  // group/from_date/to_date. Backs components/fixometer/
  // DevicesSearchTable.vue on /device/search.
  search(params) {
    return this.$get('/api/v2/devices', params)
  }

  // GET /api/v2/stats/latest-repaired-event (api-contracts-phase-c.md C6b,
  // NEW, public) -> {data: {id, waste_prevented, group: GroupSummary} |
  // null}. Replaces DeviceController::index()'s inline "most recent
  // finished event with repairs" query. Backs components/fixometer/
  // LatestRepairs.vue on /fixometer.
  latestRepairedEvent() {
    return this.$get('/api/v2/stats/latest-repaired-event')
  }
}
