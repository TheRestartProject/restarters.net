<template>
  <div>
    <div class="d-block d-md-none">
      <h2 class="d-flex d-md-none justify-content-between">
        <span class="w-100">
          <slot name="title" />
          <span v-if="count">
            ({{ count }})
          </span>
        </span>
        <span @click="toggle">
          <img class="icon" v-if="expanded" src="/images/minus-icon.svg" alt="Collapse" />
          <img class="icon" v-else src="/images/add-icon.svg" alt="Expand" />
        </span>
      </h2>
      <hr v-if="!expanded" />
      <b-collapse ref="collapse" v-model="expanded">
        <slot name="content" />
      </b-collapse>
    </div>
    <div class="d-none d-md-block">
      <h2 v-if="!hideTitle">
        <slot name="title" />
      </h2>
      <slot name="content" />
    </div>
  </div>
</template>
<script>
// This gives us a component which:
// - on desktop is always expanded, and may or may not have a title
// - on mobile has a title, an expand/contract button, and the ability to collapse by default
// - optional count on mobile to encourage clicks.

export default {
  props: {
    collapsed: {
      type: Boolean,
      required: false,
      default: false
    },
    hideTitle: {
      type: Boolean,
      required: false,
      default: false
    },
    count: {
      type: Number,
      required: false,
      default: null
    }
  },
  data () {
    return {
      expanded: true
    }
  },
  mounted() {
    this.expanded = !this.collapsed
  },
  methods: {
    toggle() {
      this.expanded = !this.expanded
    }
  }
}
</script>
<style scoped lang="scss">
.icon {
  width: 30px;
}
</style>