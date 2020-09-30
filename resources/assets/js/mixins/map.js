import { DATE_FORMAT, LEAFLET_ATTRIBUTION, LEAFLET_TILES } from '../constants'

export default {
  computed: {
    attribution() {
      return LEAFLET_ATTRIBUTION
    },
    tiles() {
      return LEAFLET_TILES
    }
  }
}