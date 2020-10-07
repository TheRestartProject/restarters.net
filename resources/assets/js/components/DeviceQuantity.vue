<template>
  <div>
    <multiselect
        v-model="quantityValue"
        :options="quantities"
        track-by="id"
        label="text"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:quantity', $event.id)"
    />
    <div />
  </div>
</template>
<script>

export default {
  props: {
    quantity: {
      type: Number,
      required: false,
      default: null
    }
  },
  computed: {
    quantities() {
      let ret = []

      for (let i = 0; i <= 10; i++) {
        ret.push({
          id: i,
          text: i
        })
      }

      return ret
    },
    quantityValue: {
      get() {
        return this.quantity ? {
          id: this.quantity,
          text: this.quantity
        } : {
          id: 1,
          text: 1
        }
      },
      set(newval) {
        this.$emit('update:quantity', newval.id)
      }
    }
  }
}
</script>