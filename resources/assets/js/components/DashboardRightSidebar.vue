<template>
  <div>
    <b-img-lazy fluid src="/images/dashboard_3.jpg" class="border border-dark border-bottom-0" />
    <div class="p-4 greyish border border-dark border-bottom-0">
      <p>TODO Where do the links go?</p>
      <p class="font-weight-bold">
<!--        eslint-disable-next-line-->
        {{ translatedSidebarIntro1 }} <a href="#">{{ translatedSidebarKit }}</a>
      </p>
      <p>
        <!--        eslint-disable-next-line-->
        {{ translatedSidebarHelp }} <a href="#">{{ translatedSidebarInTouch }}</a>
      </p>
    </div>
    <CollapsibleSection class="orange border border-dark" :show-horizontal-rule="false">
      <template slot="title">
        <div class="d-flex">
          <span class="pl-4 pr-2 pt-4">{{ translatedGettingTheMost }}</span>
          <b-img-lazy fluid src="/images/hand_doodle.svg" class="hand mr-3" />
        </div>
      </template>
      <template slot="content">
        <p>TODO Where do the links go?</p>
        <div class="pl-2 pr-2">
          <div class="pl-3 pr-3">
            <ul class="list-unstyled pt-3 pb-0 mt-0 content">
              <li>
                {{ translatedMakeSure1 }} <a href="#">{{ translatedMakeSure2 }}</a>
              </li>
              <li>
                {{ translatedLearnMore1 }} <a href="#">{{ translatedLearnMore2 }}</a>
              </li>
              <li>
                {{ translatedSkills1}} <a href="#">{{ translatedSkills2 }}</a> {{ translatedSkills3 }}
              </li>
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
    translatedSidebarKit() {
      return this.$lang.get('dashboard.sidebar_kit')
    },
    translatedSidebarHelp() {
      return this.$lang.get('dashboard.sidebar_help')
    },
    translatedSidebarInTouch() {
      return this.$lang.get('dashboard.sidebar_in_touch')
    },
    translatedGettingTheMost() {
      return this.$lang.get('dashboard.getting_the_most')
    },
    translatedMakeSure1() {
      return this.$lang.get('dashboard.make_sure_1')
    },
    translatedMakeSure2() {
      return this.$lang.get('dashboard.make_sure_2')
    },
    translatedLearnMore1() {
      return this.$lang.get('dashboard.learn_more_1')
    },
    translatedLearnMore2() {
      return this.$lang.get('dashboard.learn_more_2')
    },
    translatedSkills1() {
      return this.$lang.get('dashboard.skills_1')
    },
    translatedSkills2() {
      return this.$lang.get('dashboard.skills_2')
    },
    translatedSkills3() {
      return this.$lang.get('dashboard.skills_3')
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

a {
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