<template>
  <div class="d-flex w-100 justify-content-between">
    <multiselect
      v-model="category"
      :placeholder="translatedCategory"
      :options="categoryOptions"
      group-label="cluster"
      group-values="categories"
      :multiple="false"
      :allow-empty="false"
      deselect-label=""
      track-by="name"
      label="name"
      :group-select="false"
      :taggable="false"
      selectLabel=""
      ref="multiselect">
    </multiselect>
    <div v-b-popover.html.left :title="translatedTooltipCategory" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

// TODO Not emitting and setting
export default {
  props: {
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
  data () {
    return {
      value: null,
      category: null,
      options: [
        {
          cluster: 'Javascript',
          categories: [
            { name: 'Vue.js', category: 'Front-end' },
            { name: 'Adonis', category: 'Backend' }
          ]
        },
        {
          cluster: 'Ruby',
          categories: [
            { name: 'Rails', category: 'Backend' },
            { name: 'Sinatra', category: 'Backend' }
          ]
        },
        {
          cluster: 'Other',
          categories: [
            { name: 'Laravel', category: 'Backend' },
            { name: 'Phoenix', category: 'Backend' }
          ]
        }
      ]
    }
  },
  computed: {
    categoryOptions() {
      // TODO
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

      console.log("Options", ret, this.options)
      return ret
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
    translatedTooltipCategory() {
      return this.$lang.get('devices.tooltip_category')
    }
  },
  mounted() {
    console.log(this.$refs.multiselect)
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';
</style>