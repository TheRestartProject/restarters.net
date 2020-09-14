<template>
  <div>
    {{ attendee.volunteer.name }} TODO more info
  </div>
</template>
<script>
export default {
  props: {
    attendee: {
      type: Object,
      required: true
    }
  }
}
</script>