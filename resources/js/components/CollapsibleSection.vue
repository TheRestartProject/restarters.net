<template>
  <div :class="{
      'border-shadow': borderShadow
  }">
    <component :is="headingLevel" :class="{
      'd-flex': true,
      'd-md-none': hideTitle,
      headingClass : true,
      'justify-content-between': true,
      }" @click="toggle">
      <div class="d-flex w-100 justify-content-between align-items-center">
        <div class="d-flex flex-row flex-wrap w-100">
          <div class="d-flex flex-column justify-content-center">
            <slot name="title" />
          </div>
          <div v-if="count" :class="{
          'd-md-none' : !alwaysShowCount,
          'text-muted' : true,
          'd-flex' : true,
          'flex-column' : true,
          'justify-content-center' : true
        }">
          <span v-if="countBadge">
            &nbsp;<b-badge variant="primary" pill>{{ count }}</b-badge>
          </span>
          <span v-else :class="countClass + ' ml-1'">({{ count }})</span>
          </div>
          <slot name="title-icon" />
        </div>
        <slot name="title-right" />
      </div>
      <span class="d-inline d-md-none clickme d-flex flex-column justify-content-center">
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
    <hr v-if="showHorizontalRule" class="mt-0 d-md-none" />
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
    hideTitle: {
      type: Boolean,
      required: false,
      default: false
    },
    count: {
      type: Number,
      required: false,
      default: null
    },
    alwaysShowCount: {
      type: Boolean,
      required: false,
      default: false
    },
    countBadge: {
      type: Boolean,
      required: false,
      default: false
    },
    countClass: {
      type: String,
      required: false,
      default: 'count'
    },
    headingLevel: {
      type: String,
      required: false,
      default: 'h2'
    },
    headingClass: {
      type: String,
      required: false,
      default: 'mb-3'
    },
    showHorizontalRule: {
      type: Boolean,
      required: false,
      default: true
    },
    borderShadow: {
      type: Boolean,
      required: false,
      default: false
    },
    persist: {
      type: String,
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

    if (this.persist) {
      try {
        // We might have a stored state which overrides this.
        const stored = localStorage.getItem('collapsible-' + this.persist)

        if (stored !== null) {
          this.expanded = stored === 'false' ? false : true
        }
      } catch (e) {
        console.log("Get local failed", e)
      }
    }
  },
  methods: {
    toggle() {
      this.expanded = !this.expanded

      if (this.persist) {
        // Save state.
        try {
          localStorage.setItem('collapsible-' + this.persist, this.expanded)
        } catch (e) {
          console.log("Set local failed", e)
        }
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.icon {
  width: 30px;
}

.count {
  color: $brand-light;
}

.border-shadow {
  background-color: $white;
  border: 1px solid $black;

  @include media-breakpoint-up(md) {
    box-shadow: 5px 5px $black;
  }
}
</style>
