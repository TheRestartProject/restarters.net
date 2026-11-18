<script setup>
// Port of develop's ConfirmModal.vue: "Are you sure?" title, a message
// (default "Please confirm that you wish to proceed."), white Cancel and
// primary Confirm buttons. Confirming emits `confirm` then closes.
import { useI18n } from 'vue-i18n'

defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: null },
  message: { type: String, default: null },
  pending: { type: Boolean, default: false },
  confirmTestid: { type: String, default: 'confirm-modal-confirm' },
})
const emit = defineEmits(['update:modelValue', 'confirm'])
const { t } = useI18n()

function close() {
  emit('update:modelValue', false)
}

function confirm() {
  emit('confirm')
  close()
}
</script>

<template>
  <BModal
    :model-value="modelValue"
    :title="title || t('partials.are_you_sure')"
    @hide="close"
  >
    <p>{{ message || t('partials.please_confirm') }}</p>
    <template #footer>
      <BButton variant="white" data-testid="confirm-modal-cancel" @click="close">
        {{ t('partials.cancel') }}
      </BButton>
      <BButton variant="primary" :disabled="pending" :data-testid="confirmTestid" @click="confirm">
        {{ t('partials.confirm') }}
      </BButton>
    </template>
  </BModal>
</template>
