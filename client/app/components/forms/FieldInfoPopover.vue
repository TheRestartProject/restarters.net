<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Info "i" button that reveals a field's help text on hover/focus/click.
// The device form carries one of these on five of its fields (item type,
// category, model, problem, notes) - DeviceCategorySelect.vue,
// DeviceType.vue, DeviceModel.vue, DeviceProblem.vue and DeviceNotes.vue
// each render their own copy of the same markup, which is why it's one
// component here instead of five.
//
// The body is HTML: the tooltip strings in lang/*/devices.php contain <p>
// and <br> (devices.tooltip_category is three paragraphs), and are authored
// by us in the lang files rather than coming from user input.
defineProps({
  // Already-translated help text. May contain HTML.
  content: {
    type: String,
    required: true,
  },
  // Black on a white background (the add-device form), green where the
  // field sits on the shaded edit panel - the icon has to stay legible
  // against both.
  variant: {
    type: String,
    default: 'black',
    validator: (value) => ['black', 'brand'].includes(value),
  },
})

const { t } = useI18n()
const button = ref(null)
</script>

<template>
  <span class="field-info">
    <button
      ref="button"
      type="button"
      class="field-info__toggle"
      :aria-label="t('client.common.more_information')"
      data-testid="field-info-toggle"
    >
      <img :src="`/icons/info_ico_${variant === 'brand' ? 'green' : 'black'}.svg`" alt="" width="18" height="18">
    </button>
    <BPopover :target="button" triggers="hover focus click" html data-testid="field-info-popover">
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="content" />
    </BPopover>
  </span>
</template>

<style scoped lang="scss">
.field-info__toggle {
  background: none;
  border: 0;
  padding: 0;
  margin-left: 0.5rem;
  line-height: 1;
  vertical-align: text-bottom;
  cursor: pointer;
}
</style>
