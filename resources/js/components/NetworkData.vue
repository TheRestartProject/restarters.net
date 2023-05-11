<template>
  <div>
    <NetworkDataField v-for="key in Object.keys(currentNetworkData)" :key="'networkdata-' + key" :label="key" :value="currentNetworkData[key] ? currentNetworkData[key] : null" @update:value="update(key, $event)" class="mt-1" />
    <b-btn v-if="!showAddNew" variant="link" @click="showAddNew = true" class="small pl-0">Add new field</b-btn>
    <div v-if="showAddNew">
      <label>New field name:</label>
      <b-form-input v-model="newLabel" />
      <b-btn variant="primary" @click="addNew" class="mt-2">
        Add field
      </b-btn>
    </div>
  </div>
</template>
<script>
import Vue from 'vue'
import NetworkDataField from "./NetworkDataField"

export default {
  props: {
    networkData: {
      type: Object,
      required: true,
    },
  },
  components: {
    NetworkDataField,
  },
  data() {
    return {
      currentNetworkData: null,
      showAddNew: false,
      newLabel: null,
    }
  },
  created() {
    this.currentNetworkData = this.networkData
  },
  watch: {
    currentNetworkData: {
      handler: function (newValue) {
        if (newValue) {
          this.$emit('update:networkData', newValue)
        }
      },
      immediate: true,
    },
    networkData: {
      handler: function (newValue) {
        this.currentNetworkData = newValue
      },
      immediate: true,
    },
  },
  methods: {
    update(key, value) {
      if (key) {
        if (!this.currentNetworkData) {
          this.currentNetworkData = {}
        }

        Vue.set(this.currentNetworkData, key, value)
      }
    },
    addNew() {
      if (!this.currentNetworkData) {
        this.currentNetworkData = {}
      }

      Vue.set(this.currentNetworkData, this.newLabel, null)
      this.showAddNew = false
    }
  }
}
</script>