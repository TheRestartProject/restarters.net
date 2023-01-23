<template>
  <div class="w-100 device-select-row device-category">
    <multiselect
      :disabled="disabled"
      v-model="categoryValue"
      :placeholder="__('devices.category')"
      :options="categoryOptions"
      track-by="value"
      label="name"
      group-label="cluster"
      group-values="categories"
      :multiple="false"
      :allow-empty="allowEmpty"
      :deselect-label="allowEmpty ? __('partials.remove') : null"
      :group-select="false"
      :taggable="false"
      selectLabel=""
      ref="multiselect"
      @select=""
      :selectedLabel="allowEmpty ? __('partials.remove') : null"
    >
    </multiselect>
    <div v-b-popover.html.left="__('devices.tooltip_category')" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
    <div class="multiselect__content-wrapper d-none" />
  </div>
</template>
<script>

import { CATEGORY_MISC } from '../constants'

export default {
  props: {
    category: {
      type: Number,
      required: false,
      default: null
    },
    clusters: {
      type: Array,
      required: true
    },
    powered: {
      // The server might return a number rather than a boolean.
      type: [ Boolean, Number ],
      required: true
    },
    iconVariant: {
      type: String,
      required: false,
      default: 'black'
    },
    allowEmpty: {
      type: Boolean,
      required: false,
      default: false
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  computed: {
    categoryValue: {
      get() {
        let ret = null

        this.categoryOptions.forEach(c => {
          c.categories.forEach(o => {
            if (o.value === this.category) {
              ret = o
            }
          })
        })

        return ret
      },
      set(newval) {
        this.$emit('update:category', newval ? newval.value : null)
        this.$emit('changed')
      }
    },
    categoryOptions() {
      let ret = []

      this.clusters.forEach((cluster) => {
        let categories = []

        cluster.categories.forEach((c) => {
          if ((this.powered && c.powered) || (!this.powered && !c.powered)) {
            categories.push({
              name: this.$lang.get('strings.' + c.name),
              value: c.idcategories
            })
          }
        })

        if (categories.length) {
          ret.push({
            cluster: this.$lang.get('strings.' + cluster.name),
            categories: categories
          })
        }
      })

      if (this.powered) {
        ret.push({
          cluster: '---',
          categories: [
            {
              name: this.$lang.get('partials.category_none'),
              value: CATEGORY_MISC,
            }
          ]
        })
      }

      return ret
    },
  }
}
</script>
<style>
/*
 Increase the width to avoid scrolling.  Having trouble with v-deep here, so use a global style but restricted
 a bit by class.
*/
.device-category .multiselect__content-wrapper {
  width: 360px !important;
}

::v-deep .multiselect__tags {
  min-height: 43px;
}
</style>