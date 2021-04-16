<template>
  <b-thead class="text-center">
    <b-tr v-if="!past">
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/mail_ico.svg" :title="__('groups.volunteers_invited')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/participants.svg" :title="__('groups.volunteers_confirmed')" />
      </b-th>
      <b-th>
      </b-th>
    </b-tr>
    <b-tr v-else>
      <b-th>
      </b-th>
      <b-th>
      </b-th>
      <b-th class="d-none d-md-table-cell">
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/participants.svg" :title="__('groups.participants_attended')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/icons/volunteer_ico-thick.svg" :title="__('groups.volunteers_attended')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/trash.svg" :title="__('groups.waste_prevented')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/cloud_empty.svg" :title="__('groups.co2_emissions_prevented')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/fixed.svg" :title="__('groups.fixed_items')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/repairable_ico.svg" :title="__('groups.repairable_items')" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/dead_ico.svg" :title="__('groups.end_of_life_items')" />
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
  },
}
</script>
<style scoped>
.icon {
  width: 30px;
}
</style>