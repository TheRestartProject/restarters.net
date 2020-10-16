<template>
  <b-thead>
    <b-tr v-if="!past">
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
        <b-img src="/images/mail_ico.svg" />
      </b-th>
      <b-th>
        <b-img src="/images/participants.svg" />
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
        <b-img src="/images/participants.svg" />
      </b-th>
      <b-th>
        Drill
      </b-th>
      <b-th>
        <b-img src="/images/trash.svg" />
      </b-th>
      <b-th>
        <b-img src="/images/cloud-empty.svg" />
      </b-th>
      <b-th>
        <b-img src="/images/fixed.svg" />
      </b-th>
      <b-th>
        <b-img src="/images/repairable_ico.svg" />
      </b-th>
      <b-th>
        <b-img src="/images/dead_ico.svg" />
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