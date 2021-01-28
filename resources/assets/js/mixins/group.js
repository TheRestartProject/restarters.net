// This mixin includes unction relating to groups.

export default {
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
    volunteers() {
      let ret = []
      if (this.group && this.group.volunteers) {
        ret = this.group.volunteers
      }

      return ret
    },
    canedit() {
      return this.group ? this.group.canedit : false
    },
    ingroup() {
      return this.group ? this.group.ingroup : false
    }
  }
}