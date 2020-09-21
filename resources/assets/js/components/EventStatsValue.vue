<template>
  <div :class="className">
    <div class="event-stat-icon mt-3 mb-2">
      <b-img :src="src" class="event-stat-img" />
    </div>
    <div :class="'event-stat-count event-stat-count-' + variant">
      {{ roundedCount }} {{ unit }}
    </div>
    <!-- The translations may include HTML tags, so we need to insert as HTML. -->
    <div class="event-stat-title" v-html="translatedTitle" />
    <div class="event-stat-subtitle" v-html="translatedSubtitle" />
    <div v-if="description" class="event-stat-description pt-3 m-3" v-html="translatedDescription" />
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
    title: {
      type: String,
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
    }
  },
  computed: {
    src() {
      // All our icons are SVG files in asset/icons.
      return '/images/' + this.icon + '.svg'
    },
    className() {
      return 'event-stat event-stat-' + this.size + ' event-stat-' + this.variant
    },
    translatedTitle() {
      return this.$lang.get(this.title)
    },
    translatedSubtitle() {
      return this.$lang.get(this.subtitle)
    },
    translatedDescription() {
      return this.$lang.get(this.description)
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

.event-stat {
  border: 1px solid black;
  text-align: center !important;
  box-shadow: $black $shadow $shadow 0px 0px;
  background-color: $white;
  display: grid;
  align-items: center;
  padding: 5px;

  margin-top: 1rem !important;

  @include media-breakpoint-up(md) {
    margin-top: 0px;
  }

  &-primary {
    // Primary becomes horizontal and left-aligned at small breakpoints
    background-color: $brand-light;
    color: white;

    display: flex;
    justify-content: left;

    .event-stat-count {
      margin-left: 1rem;
    }

    .event-stat-icon {
      margin-top: 0.5rem !important;
    }

    @include media-breakpoint-up(md) {
      display: grid;
      justify-content: center;

      .event-stat-count {
        margin-left: 0px;
      }
    }
  }
}

.event-stat-count {
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

.event-stat-title {
  font-family: $font-family-third;
  font-size: 18px;
  font-weight: bold;
}

.event-stat-img {
  max-width: 46px;
}

.event-stat-icon {
  height: 41px;
}

.event-stat-description {
  border-top: 3px dashed #222;
  font-family: $font-family-third;
  font-size: 18px;
}
</style>