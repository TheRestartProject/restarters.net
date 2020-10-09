<template>
  <div class="w-100 device-select-row">
    <multiselect
      v-model="categoryValue"
      :placeholder="translatedCategory"
      :options="categoryOptions"
      track-by="value"
      label="name"
      group-label="cluster"
      group-values="categories"
      :multiple="false"
      :allow-empty="false"
      deselect-label=""
      :group-select="false"
      :taggable="false"
      selectLabel=""
      ref="multiselect"
      @select=""
    >
    </multiselect>
    <div v-b-popover.html.left="translatedTooltipCategory" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
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
    }
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
        this.$emit('update:category', newval.value)
      }
    },
    categoryOptions() {
      let ret = []

      this.clusters.forEach((cluster) => {
        let categories = []

        cluster.categories.forEach((c) => {
          if ((this.powered && c.powered) || (!this.powered && !c.powered)) {
            categories.push({
              name: c.name,
              value: c.idcategories
            })
          }
        })

        if (categories.length) {
          ret.push({
            cluster: cluster.name,
            categories: categories
          })
        }
      })

      ret.push({
        cluster: '---',
        categories: [
          {
            name: this.$lang.get('partials.category_none'),
            value: CATEGORY_MISC,
          }
        ]
      })

      return ret
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
    translatedTooltipCategory() {
      return this.$lang.get('devices.tooltip_category')
    }
  }
}
</script>