<script setup>
import { useI18n } from 'vue-i18n'
import CollapsibleSection from '~/components/CollapsibleSection.vue'

// Legacy source: resources/js/components/DashboardRightSidebar.vue. The
// legacy version had a couple of display classes that contradicted their
// parent's (a mobile-only span nested inside a desktop-only block, and vice
// versa): getting_the_most_intro was never actually reachable on any
// breakpoint (verified against the live legacy dashboard: its wrapping <p>
// is d-none d-md-block while the v-html span inside is d-inline d-md-none -
// desktop-only containing mobile-only, so it never renders anywhere), so
// that key is gone. sidebar_intro_1 is what real users saw - above the photo
// on desktop, inside the orange panel on mobile (the photo/intro box is
// desktop-only) - replicated here the same way rather than showing it twice
// on desktop.
const { t } = useI18n()
</script>

<template>
  <div data-testid="dashboard-right-sidebar">
    <div class="d-none d-md-block intro-photo" data-testid="dashboard-sidebar-photo">
      <img src="/images/dashboard_3.jpg" alt="" class="img-fluid border border-dark border-bottom-0">
      <div class="intro-text p-4 border border-dark border-top-0">
        <p class="fw-bold mb-0">
          {{ t('dashboard.sidebar_intro_1') }}
        </p>
      </div>
    </div>

    <div class="panel panel__orange getting-started mt-3" data-testid="dashboard-getting-started">
      <CollapsibleSection>
        <template #title>
          <div class="d-flex justify-content-between align-items-start">
            <h2>{{ t('dashboard.getting_the_most') }}</h2>
            <img src="/images/hand_doodle.svg" alt="" class="hand-doodle ms-3">
          </div>
        </template>

        <div class="getting-started__content">
          <!-- getting_the_most_intro is dropped entirely: develop's own
               markup makes it unreachable at any breakpoint (see the header
               comment above) - not shown here either. -->
          <p class="d-block d-md-none">{{ t('dashboard.sidebar_intro_1') }}</p>

          <ul class="list-unstyled getting-started__list">
            <!-- eslint-disable vue/no-v-html -->
            <li v-html="t('dashboard.getting_the_most_bullet1')" />
            <li v-html="t('dashboard.getting_the_most_bullet2')" />
            <li v-html="t('dashboard.getting_the_most_bullet3')" />
            <li v-html="t('dashboard.getting_the_most_bullet4')" />
            <!-- eslint-enable vue/no-v-html -->
          </ul>
        </div>
      </CollapsibleSection>
    </div>
  </div>
</template>

<style scoped>
/* $black from client/app/assets/css/_variables.scss - component-scoped
   styles compile outside the global Sass import chain so the variable
   itself isn't in scope here; see _panels.scss's header comment. */
.intro-text {
  background-color: #d0dae6;
}

.intro-photo {
  box-shadow: 5px 5px #222;
}

.getting-started :deep(a) {
  color: inherit;
  text-decoration: underline;
}

.getting-started__content {
  border-top: 3px dashed #222;
  margin-top: 1rem;
  padding-top: 1rem;
}

.getting-started__list li {
  margin-bottom: 0.75rem;
}

.getting-started__list li::before {
  content: url('/images/dashboard_line_arrow.svg');
  margin-right: 5px;
}

.hand-doodle {
  width: 70px;
  height: 86px;
  flex-shrink: 0;
}
</style>
