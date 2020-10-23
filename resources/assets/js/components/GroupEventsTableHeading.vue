<template>
  <b-thead class="text-center">
    <b-tr v-if="!past">
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/mail_ico.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/participants.svg" />
      </b-th>
      <b-th>
      </b-th>
    </b-tr>
    <b-tr v-else>
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/participants.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/icons/volunteer_ico.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/trash.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/cloud_empty.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/fixed.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/repairable_ico.svg" />
      </b-th>
      <b-th>
        <b-img class="icon" src="/images/dead_ico.svg" />
      </b-th>
    </b-tr>
  </b-thead>
</template>
<script>
export default {
  props: {
    past: {
      type: Boolean,
      required: false,
      default: false
    }
  }
}
</script>
<style scoped>
.icon {
  width: 30px;
}
</style>