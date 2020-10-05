<template>
  <div class="w-100 device-select-row">
    <multiselect
      :value="value"
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
      @select="$emit('update:category', $event.value)"
    >
    </multiselect>
    <div v-b-popover.html.left :title="translatedTooltipCategory" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

import { CATEGORY_MISC } from '../constants'

export default {
  props: {
    value: {
      type: Number,
      required: false,
      default: null
    },
    clusters: {
      type: Array,
      required: true
    },
    powered: {
      type: Boolean,
      required: true
    },
    iconVariant: {
      type: String,
      required: false,
      default: 'black'
    }
  },
  computed: {
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