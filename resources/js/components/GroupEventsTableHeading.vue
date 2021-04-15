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
        <b-img class="icon" src="/images/mail_ico.svg" :title="translatedVolunteersInvited" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/participants.svg" :title="translatedVolunteersConfirmed" />
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
        <b-img class="icon" src="/images/participants.svg" :title="translatedParticipantsAttended" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/icons/volunteer_ico-thick.svg" :title="translatedVolunteersAttended" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/trash.svg" :title="translatedWastePrevented" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/cloud_empty.svg" :title="translatedCO2EmissionsPrevented" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/fixed.svg" :title="translatedFixedItems" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/repairable_ico.svg" :title="translatedRepairableItems" />
      </b-th>
      <b-th class="d-none d-md-table-cell">
        <b-img class="icon" src="/images/dead_ico.svg" :title="translatedDeadItems" />
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
  computed: {
    translatedVolunteersInvited() {
      return this.$lang.get('groups.volunteers_invited')
    },
    translatedVolunteersConfirmed() {
      return this.$lang.get('groups.volunteers_confirmed')
    },
    translatedParticipantsAttended() {
      return this.$lang.get('groups.participants_attended')
    },
    translatedVolunteersAttended() {
      return this.$lang.get('groups.volunteers_attended')
    },
    translatedWastePrevented() {
      return this.$lang.get('groups.waste_prevented')
    },
    translatedCO2EmissionsPrevented() {
      return this.$lang.get('groups.co2_emissions_prevented')
    },
    translatedFixedItems() {
      return this.$lang.get('groups.fixed_items')
    },
    translatedRepairableItems() {
      return this.$lang.get('groups.repairable_items')
    },
    translatedDeadItems() {
      return this.$lang.get('groups.end_of_life_items')
    },
  }
}
</script>
<style scoped>
.icon {
  width: 30px;
}
</style>