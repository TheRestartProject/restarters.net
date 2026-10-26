<template>
  <div :class="className">
    <div class="impact-stat-icon" v-if="!iconInline">
      <b-img :src="src" class="impact-stat-img" :width="iconWidth" />
    </div>
    <div :class="'impact-stat-count impact-stat-count-' + variant + (iconInline ? ' d-flex' : '')">
      {{ printableCount }} {{ unit }}
      <div class="impact-stat-icon" v-if="iconInline">
        <b-img :src="src" class="impact-stat-img" :width="iconWidth" />
      </div>
    </div>
    <!-- The translations may include HTML tags, so we need to insert as HTML. -->
    <div class="impact-stat-title" v-if="title" v-html="translatedTitle" />
    <div class="impact-stat-title" v-if="percent !== null">
      {{ percent }}%
    </div>
    <div class="impact-stat-subtitle" v-if="subtitle" v-html="translatedSubtitle" />
    <div v-if="description && count > 0" class="impact-stat-description d-flex" >
      <span v-html="translatedDescription" />
      <b-img v-if="popover" v-b-popover.html="popover" class="ml-2 icon-info clickable" :src="imageUrl('/icons/info_ico_green.svg')" />
    </div>
    <div class="image d-flex justify-content-around" v-if="image">
      <b-img :src="image" />
    </div>
    <div class="d-flex justify-content-end impact-stat-share" v-if="share">
      <b-btn href="#" variant="link" @click="shareThis" class="text-brand">
        {{ __('partials.share_this') }}
      </b-btn>
    </div>
  </div>
</template>
<script>
import images from '../mixins/images'

export default {
  mixins: [images],
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
    },
    countIsNumber: {
      type: Boolean,
      required: false,
      default: true
    },
    iconInline: {
      type: Boolean,
      required: false,
      default: false
    },
    image: {
      type: String,
      required: false,
      default: null
    },
    popover: {
      type: String,
      required: false,
      default: null
    },
    share: {
      type: Boolean,
      required: false,
      default: false
    },
    roundTo: {
      type: Number,
      required: false,
      default: 0
    },
    aside: {
      // Put the description alongside the icon and the figure, rather than below them.
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    src() {
      // All our icons are SVG files in asset/icons.
      return this.imageUrl('/images/' + this.icon + '.svg')
    },
    className() {
      return 'impact-stat impact-stat-' + this.size + ' impact-stat-' + this.variant + (this.border ? ' hasBorder' : '') + (this.aside ? ' impact-stat-aside' : '')
    },
    translatedTitle() {
      return this.translate ? this.__(this.title, { count: this.roundedCount }) : this.title
    },
    translatedSubtitle() {
      return this.translate ? this.__(this.subtitle) : this.subtitle
    },
    translatedDescription() {
      return this.translate ? this.__(this.description) : this.description
    },
    roundedCount() {
      if (this.roundTo) {
        return Math.round(this.count * Math.pow(10, this.roundTo)) / Math.pow(10, this.roundTo)
      } else {
        return this.countIsNumber ? Math.round(this.count) : this.count
      }
    },
    printableCount() {
      return this.countIsNumber ? this.roundedCount.toLocaleString() : this.count
    }
  },
  methods: {
    shareThis() {
      this.$emit('share')
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

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

  &-primary-black {
    background-color: $brand-light;
    color: black;
  }

  &-primary, &-primary-black {
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

  &.impact-stat-aside {
    // The box has a left side with the icon, the figure and the title, and a right side with the
    // description and the share link.  The rows are the same as the other stats, so that the
    // spacing on the left matches them.
    grid-template-columns: auto 1fr;
    grid-template-rows: 55px 54px auto;

    &::after {
      // A dashed line separating the two sides of the box.
      content: '';
      grid-column: 1;
      grid-row: 1 / -1;
      align-self: stretch;
      border-right: 3px dashed #222;
      pointer-events: none;
    }

    .impact-stat-icon {
      grid-column: 1;
      grid-row: 1;
      padding-right: 1rem;
    }

    .impact-stat-count {
      grid-column: 1;
      grid-row: 2;
      padding-right: 1rem;
    }

    .impact-stat-title {
      grid-column: 1;
      grid-row: 3;
      // Let the title wrap, rather than squeeze the description on the right.
      max-width: 170px;
      margin: 0 auto;
      padding-right: 1rem;
    }

    .impact-stat-description {
      grid-column: 2;
      grid-row: 1 / 3;
      align-self: end;
      border-top: none;
      font-size: 16px;
      padding-top: 0;
      padding-left: 1rem;
      margin: 0;
    }

    .impact-stat-share {
      grid-column: 2;
      grid-row: 3;
      align-self: end;

      .btn {
        padding: 0;
        font-size: 0.875rem;
      }
    }
  }

  @include media-breakpoint-down(md) {
    &.impact-stat-aside {
      // On smaller screens the two sides are stacked, with the figure above the description.
      grid-template-columns: 1fr;
      grid-template-rows: 55px 54px auto auto auto auto;

      &::after {
        grid-column: 1;
        grid-row: 4;
        align-self: start;
        border-right: none;
        border-top: 3px dashed #222;
      }

      .impact-stat-icon {
        grid-column: 1;
        grid-row: 1;
        padding-right: 0;
      }

      .impact-stat-count {
        grid-column: 1;
        grid-row: 2;
        padding-right: 0;
      }

      .impact-stat-title {
        grid-column: 1;
        grid-row: 3;
        max-width: none;
        padding-right: 0;
        padding-bottom: 0.5rem;
      }

      .impact-stat-description {
        grid-column: 1;
        grid-row: 5;
        align-self: start;
        justify-content: center;
        padding-top: 0.5rem;
        padding-left: 0;
      }

      .impact-stat-share {
        grid-column: 1;
        grid-row: 6;
        align-self: start;
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

  &-primary-black {
    background-color: $brand-light;
    color: black;
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
  margin: 0.5rem 0;
}

.impact-stat-description {
  border-top: 3px dashed #222;
  font-family: $font-family-third;
  font-size: 18px;
  padding-top: 1rem;
  margin: 1rem;
  // The info icon is a flex item, so without this it would be stretched to the height of the text.
  align-items: center;
}

.image {
  img {
    height: 150px;
  }
}
</style>
