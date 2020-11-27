<template>
  <div>
    <b-table
        :fields="fields"
        :items="items"
        :per-page="10"
        :current-page="currentPage"
        sort-null-last
        thead-tr-class="d-none d-md-table-row">
    </b-table>
  </div>
</template>
<script>
const axios = require('axios')

export default {
  props: {
    powered: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      currentPage: 1
    }
  },
  computed: {
    fields() {
      let ret = [
        { key: 'iddevices', label: '' }
      ]

      if (this.powered) {
        ret.push({ key: 'model', label: this.translatedModel })
        ret.push({ key: 'brand', label: this.translatedBrand })
      } else {
        ret.push({ key: 'model', label: this.translatedModelOrType })
      }

      ret.push({ key: 'group', label: this.translatedGroup })
      ret.push({ key: 'status', label: this.translatedStatus })
      ret.push({ key: 'date', label: this.translatedDevicesDate })

      return ret
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
    translatedBrand() {
      return this.$lang.get('devices.brand')
    },
    translatedModel() {
      return this.$lang.get('devices.model')
    },
    translatedModelOrType() {
      return this.$lang.get('devices.model_or_type')
    },
    translatedGroup() {
      return this.$lang.get('devices.group')
    },
    translatedStatus() {
      return this.$lang.get('devices.status')
    },
    translatedDevicesDate() {
      return this.$lang.get('devices.devices_date')
    },
  },
  methods: {
    async items(ctx, callback) {
      console.log("Items called", ctx)
      // Don't use store - we don't need this to be reactive.
      const ret = await axios.get('/api/devices/' + ctx.currentPage + '/' + ctx.perPage)

      console.log("Returned", ret)
      callback([])
    }
  }
}
</script>