<template>
  <div>
    <component :is="headerLevel" :class="{
      'd-flex': true,
      'd-md-none': hideTitle,
      'mb-3': true,
      'justify-content-between': true
      }" @click="toggle">
      <span>
        <slot name="title" />
        <span v-if="count" class="d-inline d-md-none text-muted">
          (<span class="count">{{ count }}</span>)
        </span>
      </span>
      <span class="d-inline d-md-none">
        <img class="icon" v-if="expanded" src="/images/minus-icon.svg" alt="Collapse" />
        <img class="icon" v-else src="/images/add-icon.svg" alt="Expand" />
      </span>
    </component>
    <div :class="{
      'd-none': !expanded,
      'd-md-block': true
    }">
      <slot name="content" />
    </div>
    <hr class="mt-0 d-md-none" />
  </div>
</template>
<script>
// This gives us a component which:
// - on desktop is always expanded, and may or may not have a title
// - on mobile has a title, an expand/contract button, and the ability to collapse by default
// - optional count on mobile to encourage clicks.
// The class-wrangling is complex because Vue doesn't let you use the same slot multiple times in the same component.

export default {
  props: {
    collapsed: {
      type: Boolean,
      required: false,
      default: false
    },
    headerLevel: {
      type: String,
      required: false,
      default: 'h2'
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
@import 'resources/global/css/_variables';

.icon {
  width: 30px;
}

.count {
  color: $brand-light;
}

.text-muted {
  font-size: 28px;
}
</style>