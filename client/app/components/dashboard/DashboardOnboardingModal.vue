<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['dismiss'])

const { t } = useI18n()

// Each slide carries its own alt text. These are content images in an
// onboarding slideshow, not decoration: app.blade.php describes all three
// ("Two Restarters attempting a fix" etc), and rendering alt="" instead
// declares them decorative, so a screen reader user is told nothing at all.
// develop hardcodes the English; translated here.
const slides = [
  {
    image: '/images/onboarding/onboarding-1.webp',
    alt: 'client.onboarding.slide1_alt',
    heading: 'onboarding.slide1_heading',
    content: 'onboarding.slide1_content',
  },
  {
    image: '/images/onboarding/onboarding-2.webp',
    alt: 'client.onboarding.slide2_alt',
    heading: 'onboarding.slide2_heading',
    content: 'onboarding.slide2_content',
  },
  {
    image: '/images/onboarding/onboarding-3.webp',
    alt: 'client.onboarding.slide3_alt',
    heading: 'onboarding.slide3_heading',
    content: 'onboarding.slide3_content',
  },
]

const current = ref(0)

function next() {
  if (current.value < slides.length - 1) {
    current.value += 1
  }
}

function previous() {
  if (current.value > 0) {
    current.value -= 1
  }
}

function dismiss() {
  current.value = 0
  emit('dismiss')
}
</script>

<template>
  <BModal
    :model-value="show"
    no-footer
    hide-header
    data-testid="onboarding-modal"
    @hide="dismiss"
  >
    <button
      type="button"
      class="btn-close float-end"
      :aria-label="t('onboarding.finishing_action')"
      data-testid="onboarding-close"
      @click="dismiss"
    />

    <article :data-testid="`onboarding-slide-${current}`">
      <img
        :src="slides[current].image"
        class="rounded-circle img-fluid"
        width="250"
        :alt="t(slides[current].alt)"
      >
      <h1>{{ t(slides[current].heading) }}</h1>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div v-html="t(slides[current].content)" />
    </article>

    <div class="d-flex justify-content-between mt-3">
      <BButton
        variant="link"
        :disabled="current === 0"
        data-testid="onboarding-previous"
        @click="previous"
      >
        {{ t('onboarding.previous') }}
      </BButton>

      <BButton
        v-if="current < slides.length - 1"
        variant="link"
        data-testid="onboarding-next"
        @click="next"
      >
        {{ t('onboarding.next') }}
      </BButton>
      <BButton v-else variant="primary" data-testid="onboarding-finish" @click="dismiss">
        {{ t('onboarding.finishing_action') }}
      </BButton>
    </div>
  </BModal>
</template>
