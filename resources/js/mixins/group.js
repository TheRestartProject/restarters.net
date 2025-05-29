// This mixin includes unction relating to groups.

export default {
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
    volunteers() {
      return this.$store.getters['volunteers/byGroup'](this.idgroups) || []
    },
    canedit() {
      return this.group ? this.group.canedit : false
    },
    candemote() {
      return this.group ? this.group.candemote : false
    },
    ingroup() {
      return this.group ? this.group.ingroup : false
    }
  }
}