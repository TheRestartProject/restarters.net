import { DATE_FORMAT, LEAFLET_ATTRIBUTION, leafletTiles } from '../constants'

export default {
  computed: {
    attribution() {
      return LEAFLET_ATTRIBUTION
    },
    tiles() {
      return leafletTiles()
    }
  }
}