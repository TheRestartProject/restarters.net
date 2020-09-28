// This mixin includes lots of function relating to groups.
// TODO In due course the group will move into the store and we'll just pass the id.  All the other props will then
// become computed data in here.
const htmlToText = require('html-to-text');

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
    groupList: {
      type: Array,
      required: false,
      default: function () { return [] }
    },
    userGroups: {
      type: Array,
      required: false,
      default: function () { return [] }
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
  },
  computed: {
    free_text() {
      // Strip HTML
      let ret = htmlToText.fromString(this.group.free_text);

      // Remove duplicate blank lines.
      ret = ret.replace(/(\r\n|\r|\n){2,}/g, '$1\n');

      return ret
    }
  }
}