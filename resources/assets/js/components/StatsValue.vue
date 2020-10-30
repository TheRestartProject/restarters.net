<template>
  <div :class="className">
    <div class="impact-stat-icon mt-2 mb-2">
      <b-img :src="src" class="impact-stat-img" :width="iconWidth" />
    </div>
    <div :class="'impact-stat-count impact-stat-count-' + variant">
      {{ roundedCount }} {{ unit }}
    </div>
    <!-- The translations may include HTML tags, so we need to insert as HTML. -->
    <div class="impact-stat-title" v-if="title" v-html="translatedTitle" />
    <div class="impact-stat-title" v-if="percent !== null">
      {{ percent }}%
    </div>
    <div class="impact-stat-subtitle" v-html="translatedSubtitle" />
    <div v-if="description" class="impact-stat-description pt-3 m-3" v-html="translatedDescription" />
  </div>
</template>
<script>
export default {
  props: {
    variant: {
      type: String,
      required: false,
      default: 'secondary'
    },
    size: {
      type: String,
      required: false,
      default: 'md'
    },
    count: {
      type: Number,
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    iconWidth: {
      type: Number,
      required: false,
      default: null
    },
    title: {
      type: String,
      required: false,
      default: null
    },
    translate: {
      type: Boolean,
      required: false,
      default: true
    },
    percent: {
      type: Number,
      required: false,
      default: null
    },
    subtitle: {
      type: String,
      required: false,
      default: null
    },
    description: {
      type: String,
      required: false,
      default: null
    },
    unit: {
      type: String,
      required: false,
      default: null
    },
    border: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  computed: {
    src() {
      // All our icons are SVG files in asset/icons.
      return '/images/' + this.icon + '.svg'
    },
    className() {
      return 'impact-stat impact-stat-' + this.size + ' impact-stat-' + this.variant + (this.border ? ' hasBorder' : '')
    },
    translatedTitle() {
      return this.translate ? this.$lang.choice(this.title, this.roundedCount) : this.title
    },
    translatedSubtitle() {
      return this.translate ? this.$lang.get(this.subtitle) : this.subtitle
    },
    translatedDescription() {
      return this.translate ? this.$lang.get(this.description) : this.description
    },
    roundedCount() {
      return Math.round(this.count).toLocaleString()
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

/* Using a prefix to avoid possible collision with any global style rules.
 * 'impact' can refer to either a repair related impact stat, or an environmental impact stat.  */
.impact-stat {
  text-align: center !important;
  background-color: $white;
  display: grid;
  align-items: center;
  padding: 5px;
  margin-top: 1rem !important;
  grid-template-columns: 1fr;
  grid-template-rows: 55px 54px auto auto auto;

  &.hasBorder {
    border: 1px solid black;
    box-shadow: $black $shadow $shadow 0px 0px;
  }

  @include media-breakpoint-up(md) {
    margin-top: 0px;
  }

  &-primary, &-brand {
    // Primary becomes horizontal and left-aligned at small breakpoints
    background-color: $brand-light;
    color: white;
  }

  &-primary {
    // Primary becomes horizontal and left-aligned at small breakpoints
    display: flex;
    justify-content: center;

    .impact-stat-icon {
      margin-right: 0.5rem;
      margin-top: 0.5rem !important;
      height: auto;
    }

    .impact-stat-count {
      margin-right: 0.5rem;
    }

    @include media-breakpoint-up(md) {
      display: grid;
      justify-content: center;

      .impact-stat-count {
        margin-right: 0px;
      }

      .impact-stat-icon {
        margin-right: 0px;
      }
    }
  }
}

.impact-stat-count {
  font-family: $font-family-third;
  font-size: 36px;
  font-weight: bold;

  &-primary {
    background-color: $brand-light;
    color: white;
  }

  &-secondary {
    background-color: white;
    color: $brand-light;
  }
}

.impact-stat-title {
  font-family: $font-family-third;
  font-size: 18px;
  font-weight: bold;
}

.impact-stat-img {
  height: 46px;
}

.impact-stat-icon {
  height: 41px;
}

.impact-stat-description {
  border-top: 3px dashed #222;
  font-family: $font-family-third;
  font-size: 18px;
}
</style>
