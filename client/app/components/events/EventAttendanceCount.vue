<script setup>
import { ref, watch } from 'vue'

// Ports EventAttendanceCount.vue: the +/- stepper next to "Participants"/
// "Volunteers" (gap 13, api-gaps.md - unblocked now PATCH /api/v2/events/
// {id} accepts optional participants/volunteers, replacing legacy's own
// POST /party/update-quantity + update-volunteerquantity routes). Renders
// plain text when !canedit, same as develop.
const props = defineProps({
  count: {
    type: Number,
    default: 0,
  },
  canedit: {
    type: Boolean,
    default: false,
  },
  testid: {
    type: String,
    default: 'event-attendance-count',
  },
})

const emit = defineEmits(['change'])

const current = ref(Math.max(props.count ?? 0, 0))

// A save can fail (network/permission race) - stay in sync with whatever
// value the parent ultimately confirms, rather than trusting the optimistic
// local edit forever.
watch(
  () => props.count,
  (val) => {
    current.value = Math.max(val ?? 0, 0)
  },
)

function inc() {
  current.value++
  emit('change', current.value)
}

function dec() {
  if (current.value > 0) {
    current.value--
    emit('change', current.value)
  }
}

// Legacy's set(): fires on keyup rather than waiting for blur/change, since
// a plain `change` event doesn't fire while still focused in the field.
function onInput(event) {
  const parsed = parseInt(event.target.value, 10)
  current.value = Number.isNaN(parsed) || parsed < 0 ? 0 : parsed
  emit('change', current.value)
}
</script>

<template>
  <div v-if="canedit" class="attendance-count-wrapper d-flex align-items-center gap-1" :data-testid="testid">
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm attendance-count-step"
      :data-testid="`${testid}-dec`"
      @click="dec"
    >
      <img src="/images/minus-icon.svg" alt="-" width="16" height="16">
    </button>
    <input
      type="number"
      min="0"
      step="1"
      class="form-control form-control-sm text-center attendance-count-input"
      :value="current"
      :data-testid="`${testid}-input`"
      @input="onInput"
    >
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm attendance-count-step"
      :data-testid="`${testid}-inc`"
      @click="inc"
    >
      <img src="/images/add-icon.svg" alt="+" width="16" height="16">
    </button>
  </div>
  <div v-else class="h3 mb-0" :data-testid="testid">{{ current }}</div>
</template>

<style scoped>
.attendance-count-wrapper {
  width: 140px;
}

.attendance-count-step {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.attendance-count-input {
  -moz-appearance: textfield;
}

.attendance-count-input::-webkit-outer-spin-button,
.attendance-count-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
