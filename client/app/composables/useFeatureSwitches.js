// The feature switches a network coordinator or group host can set (RES-2063).
// Keys match the API's feature registry; labels come from client.features.*.
export const FEATURE_KEYS = ['item_weight', 'custom_fields']

export const MAX_CUSTOM_FIELDS = 10

// A custom field's label followed by a colon, unless it already ends in punctuation ("First visit?").
export function fieldLabel(label) {
  return /[?!:.]$/.test(label) ? label : `${label}:`
}
