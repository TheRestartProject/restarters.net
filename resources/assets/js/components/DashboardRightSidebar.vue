<template>
  <div>
    <div class="d-none d-md-block">
      <b-img-lazy fluid src="/images/dashboard_3.jpg" class="border border-dark border-bottom-0" />
      <div class="pt-4 pl-4 pr-4 pb-2 greyish border border-dark border-bottom-0">
        <p class="font-weight-bold">
          <!--        eslint-disable-next-line-->
          {{ translatedSidebarIntro1 }}
          <span class="d-inline d-md-none">
            {{ translatedSidebarKit1 }} <a href="https://talk.restarters.net/t/how-to-run-a-repair-event-the-restart-party-kit/324">{{ translatedSidebarKit2 }}</a>
          </span>
        </p>
        <p class="d-block d-md-none">
          <!--        eslint-disable-next-line-->
          {{ translatedSidebarHelp }} <a href="https://talk.restarters.net/c/community-repair/5">{{ translatedSidebarLetUsKnow }}</a>.
        </p>
      </div>
    </div>
    <CollapsibleSection class="orange border border-dark" :show-horizontal-rule="false">
      <template slot="title">
        <div class="d-flex">
          <span class="pl-4 pr-2 pt-4">{{ translatedGettingTheMost }}</span>
          <b-img-lazy fluid src="/images/hand_doodle.svg" class="hand mr-3" />
        </div>
      </template>
      <template slot="content">
        <div class="pl-2 pr-2">
          <div class="pl-3 pr-3">
            <p>
              <!--        eslint-disable-next-line-->
              <span v-html="translatedGettingTheMostIntro" class="d-inline d-md-none" />
              <!--        eslint-disable-next-line-->
              <span v-html="translatedGettingTheMostKit" class="d-none d-md-inline" />
            </p>
            <ul class="list-unstyled pt-3 pb-0 mt-0 content">
              <!--        eslint-disable-next-line-->
              <li v-html="translatedGettingTheMostBullet1" />
              <!--        eslint-disable-next-line-->
              <li v-html="translatedGettingTheMostBullet2" />
              <!--        eslint-disable-next-line-->
              <li v-html="translatedGettingTheMostBullet3" />
              <!--        eslint-disable-next-line-->
              <li v-html="translatedGettingTheMostBullet4" />
            </ul>
          </div>
        </div>
      </template>
    </CollapsibleSection>
  </div>
</template>
<script>
import CollapsibleSection from './CollapsibleSection'
export default {
  components: {CollapsibleSection},
  computed: {
    translatedSidebarIntro1() {
      return this.$lang.get('dashboard.sidebar_intro_1')
    },
    translatedSidebarKit1() {
      return this.$lang.get('dashboard.sidebar_kit2')
    },
    translatedSidebarKit2() {
      return this.$lang.get('dashboard.sidebar_kit2')
    },
    translatedSidebarHelp() {
      return this.$lang.get('dashboard.sidebar_help')
    },
    translatedSidebarLetUsKnow() {
      return this.$lang.get('dashboard.sidebar_let_us_know')
    },
    translatedGettingTheMost() {
      return this.$lang.get('dashboard.getting_the_most')
    },
    translatedGettingTheMostIntro() {
      return this.$lang.get('dashboard.getting_the_most_intro')
    },
    translatedGettingTheMostKit() {
      return this.$lang.get('dashboard.getting_the_most_kit')
    },
    translatedGettingTheMostBullet1() {
      return this.$lang.get('dashboard.getting_the_most_bullet1')
    },
    translatedGettingTheMostBullet2() {
      return this.$lang.get('dashboard.getting_the_most_bullet2')
    },
    translatedGettingTheMostBullet3() {
      return this.$lang.get('dashboard.getting_the_most_bullet3')
    },
    translatedGettingTheMostBullet4() {
      return this.$lang.get('dashboard.getting_the_most_bullet4')
    },
  }
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

/deep/ a {
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
</style>