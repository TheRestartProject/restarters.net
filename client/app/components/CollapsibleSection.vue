<script setup>
import { ref, useId } from 'vue'
import { useI18n } from 'vue-i18n'

// Mobile-collapsible section (legacy resources/js/components/CollapsibleSection.vue):
// on desktop the content is always shown; on mobile the heading carries a "+"/"−"
// toggle that collapses the body. Used by the dashboard section cards, whose
// headings were collapsible on mobile in the legacy app.
const props = defineProps({
  // Start collapsed on mobile (desktop is always expanded regardless).
  collapsedOnMobile: { type: Boolean, default: false },
})

const { t } = useI18n()

const expanded = ref(!props.collapsedOnMobile)

// Ties the control to the region it controls, so aria-controls has something
// to point at. useId() rather than a hand-rolled counter: a counter declared
// in <script setup> is per-instance and would hand every section the same id.
const bodyId = useId()

function toggle() {
  expanded.value = !expanded.value
}
</script>

<template>
  <div class="collapsible-section">
    <div
      class="collapsible-section__header d-flex align-items-center"
      data-testid="collapsible-header"
      @click="toggle"
    >
      <!-- flex-grow-1 lets a title that has its own justify-between layout
           (e.g. Your Groups' heading + "newly added" badge) span the row. -->
      <div class="flex-grow-1">
        <slot name="title" />
      </div>
      <!-- Mobile-only expand/collapse affordance; desktop keeps the body open.
           A real <button>: this was a <span>, which cannot be tabbed to or
           activated from the keyboard, and on which aria-expanded is ignored
           because a span has no role that supports it. The glyph is the whole
           visible content, so the control also needs a name of its own -
           otherwise it announces as "+".
           @click.stop because the header row toggles too (tap anywhere on
           mobile); without it the button's click would bubble and toggle
           twice, i.e. do nothing. -->
      <button
        type="button"
        class="collapsible-section__toggle d-md-none"
        :aria-expanded="expanded"
        :aria-controls="bodyId"
        :aria-label="expanded ? t('client.common.collapse') : t('client.common.expand')"
        data-testid="collapsible-toggle"
        @click.stop="toggle"
      >
        <img v-if="expanded" :src="'/images/minus-icon.svg'" alt="" class="icon">
        <img v-else :src="'/images/add-icon.svg'" alt="" class="icon">
      </button>
    </div>

    <div :id="bodyId" :class="{ 'd-none d-md-block': !expanded }" data-testid="collapsible-body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.collapsible-section__toggle {
  background: none;
  border: 0;
  padding: 0;
  padding-left: 0.5rem;
  cursor: pointer;
}

.collapsible-section__toggle .icon {
  width: 30px;
}

@media (max-width: 767.98px) {
  .collapsible-section__header {
    cursor: pointer;
  }
}
</style>
