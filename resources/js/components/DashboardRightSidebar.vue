<template>
  <div>
    <div class="d-none d-md-block intro">
      <b-img-lazy fluid src="/images/dashboard_3.jpg" class="border border-dark border-bottom-0" />
      <div class="pt-4 pl-4 pr-4 pb-2 greyish border border-dark border-bottom-0">
        <p class="font-weight-bold">
          <!--        eslint-disable-next-line-->
          {{ __('dashboard.sidebar_intro_1') }}
          <span class="d-inline d-md-none">
            {{ __('dashboard.sidebar_kit1') }} <a href="https://talk.restarters.net/session/sso?return_path=https://talk.restarters.net/t/how-to-run-a-repair-event-the-restart-party-kit/324">{{ __('dashboard.sidebar_kit2') }}</a>
          </span>
        </p>
        <p class="d-block d-md-none">
          <!--        eslint-disable-next-line-->
          {{ __('dashboard.sidebar_help') }} <a href="https://talk.restarters.net/session/sso?return_path=https://talk.restarters.net/c/community-repair/5">{{ __('dashboard.sidebar_let_us_know') }}</a>.
        </p>
      </div>
    </div>
    <CollapsibleSection class="orange border border-dark" border-shadow :show-horizontal-rule="false" persist="dasbboard-sidebar">
      <template slot="title">
        <div class="d-flex">
          <span class="pl-4 pr-2 pt-4">{{ __('dashboard.getting_the_most') }}</span>
          <b-img-lazy fluid src="/images/hand_doodle.svg" class="hand mr-3" />
        </div>
      </template>
      <template slot="content">
        <div class="pl-2 pr-2">
          <div class="pl-3 pr-3">
            <p class="d-none d-md-block">
              <!--        eslint-disable-next-line-->
              <span v-html="__('dashboard.getting_the_most_intro')" class="d-inline d-md-none" />
            </p>
            <!--        eslint-disable-next-line-->
            <p v-html="__('dashboard.sidebar_intro_1')" class="d-block d-md-none" />
            <ul class="list-unstyled pt-3 pb-0 mt-0 content">
              <!--        eslint-disable-next-line-->
              <li v-html="__('dashboard.getting_the_most_bullet1')" />
              <!--        eslint-disable-next-line-->
              <li v-html="__('dashboard.getting_the_most_bullet2')" />
              <!--        eslint-disable-next-line-->
              <li v-html="__('dashboard.getting_the_most_bullet3')" />
              <!--        eslint-disable-next-line-->
              <li v-html="__('dashboard.getting_the_most_bullet4')" />
            </ul>
          </div>
        </div>
      </template>
    </CollapsibleSection>
  </div>
</template>
<script>
import CollapsibleSection from './CollapsibleSection.vue'
export default {
  components: {CollapsibleSection},
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.greyish {
  background-color: #d0dae6;
}

.orange {
  background-color: $brand-orange;
}

::v-deep a {
  color: unset;
  text-decoration: underline;
}

li::before {
  content: url('/images/dashboard_line_arrow.svg');
  margin-right: 5px;
}

.content {
  border-top: 3px dashed black;
}

.hand {
  width: 70px;
  height: 86px;
}

.intro {
  @include media-breakpoint-up(md) {
    box-shadow: 5px 5px $black;
  }
}
</style>