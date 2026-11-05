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
    <!-- Legacy source: resources/views/layouts/app.blade.php's #onboarding
         modal + resources/js/app.js's onboarding() (a slick carousel) +
         resources/sass/_onboarding.scss. The legacy close (X) only gets its
         `.close--visible` class once the slick carousel's `beforeChange`
         reports nextSlide === 2 (the last of 3 slides) - reproduced here as
         a v-if gated on the last slide, not shown throughout. Likewise
         legacy's modal-prev is a slick arrow: slick's `slick-disabled` class
         (`display: none !important`) removes it entirely on the first
         slide, not merely :disabled. -->
    <div class="onboarding">
      <button
        v-if="current === slides.length - 1"
        type="button"
        class="btn-close float-end"
        :aria-label="t('onboarding.finishing_action')"
        data-testid="onboarding-close"
        @click="dismiss"
      />

      <article :data-testid="`onboarding-slide-${current}`">
        <img
          :src="slides[current].image"
          class="rounded-circle img-fluid onboarding__image"
          width="250"
          :alt="t(slides[current].alt)"
        >
        <h1>{{ t(slides[current].heading) }}</h1>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="onboarding__body" v-html="t(slides[current].content)" />
      </article>

      <!-- Slide-position indicator, standing in for slick's auto-generated
           `dots: true` pager. -->
      <ul class="onboarding__dots" data-testid="onboarding-dots">
        <li
          v-for="(slide, index) in slides"
          :key="index"
          class="onboarding__dot"
          :class="{ 'onboarding__dot--active': index === current }"
        />
      </ul>

      <BButton
        v-if="current > 0"
        variant="link"
        class="onboarding__nav onboarding__nav--prev"
        data-testid="onboarding-previous"
        @click="previous"
      >
        {{ t('onboarding.previous') }}
      </BButton>

      <BButton
        v-if="current < slides.length - 1"
        variant="link"
        class="onboarding__nav onboarding__nav--next"
        data-testid="onboarding-next"
        @click="next"
      >
        {{ t('onboarding.next') }}
      </BButton>
      <BButton
        v-else
        variant="primary"
        class="onboarding__nav onboarding__nav--finish"
        data-testid="onboarding-finish"
        @click="dismiss"
      >
        {{ t('onboarding.finishing_action') }}
      </BButton>
    </div>
  </BModal>
</template>

<style scoped>
/* Ported from resources/sass/_onboarding.scss (a scoped rebuild - the modal
   markup here isn't legacy's raw #onboarding/.modal-slideshow, so the
   selectors target this component's own classes rather than
   .modal/.modal-dialog/.modal-content). */
.onboarding {
  position: relative;
  padding-bottom: 56px;
}

/* resources/sass/_onboarding.scss: .modal-dialog max-width: 615px at lg+. */
:deep(.modal-dialog) {
  @media (min-width: 992px) {
    max-width: 615px;
  }
}

.onboarding article {
  padding-bottom: 10px;
}

.onboarding h1 {
  font-weight: normal;
  font-size: 26px;
  line-height: 1;
  margin: 0 0 15px;
  text-align: center;
}

.onboarding__image {
  display: block;
  margin: 0 auto 25px;
  max-width: 180px;
}

@media (min-width: 992px) {
  .onboarding__image {
    max-width: 100%;
  }
}

/* v-html content (slideN_content, lang/en/onboarding.php) renders raw <p>/
   <ul> markup that bypasses the compiler, so it needs :deep() to be reached
   by scoped styles. */
.onboarding__body :deep(p) {
  font-size: 15px;
  line-height: 1.3;
  text-align: center;
}

.onboarding__body :deep(ul) {
  font-size: 15px;
  text-align: center;
  list-style-type: square;
  list-style-position: inside;
}

.onboarding__dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.onboarding__dot {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  background-color: #81cad3;
}

.onboarding__dot--active {
  background-color: #000;
}

.onboarding__nav {
  position: absolute;
}

.onboarding__nav--prev {
  left: 40px;
  bottom: 25px;
}

.onboarding__nav--next {
  right: 40px;
  bottom: 25px;
}

.onboarding__nav--finish {
  right: 40px;
  bottom: 32px;
}
</style>
