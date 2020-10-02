// This mixin includes lots of function relating to groups.
// TODO In due course the group will move into the store and we'll just pass the id.  All the other props will then
// become computed data in here.

export default {
  props: {
    groupId: {
      type: Number,
      required: true
    },
    group: {
      type: Object,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    ingroup: {
      type: Boolean,
      required: false,
      default: false
    },
    volunteers: {
      type: Array,
      required: false,
      default: function () { return [] }
    },
  }
}