<template>
  <div class="layout">
      <div class="top-layout">
          <div>
            <MicrotaskingCallToAction class="mb-4" />
            <MicrotaskingVolunteering
              class="mb-4"
              :total-contributions="totalContributions"
              :current-user-quests="currentUserQuests"
              :current-user-contributions="currentUserContributions"
            />
          </div>
          <div class="ml-md-5">
              <b-img src="" />
              <MicrotaskingNews />
          </div>
      </div>
      <div>
          <MicrotaskingDiscussion :topics="topics" :see-all-topics-link="seeAllTopicsLink" />
      </div>
  </div>
</template>
<script>
import MicrotaskingCallToAction from './MicrotaskingCallToAction'
import MicrotaskingVolunteering from './MicrotaskingVolunteering'
import MicrotaskingDiscussion from './MicrotaskingDiscussion'
import MicrotaskingNews from './MicrotaskingNews'
export default {
  components: {MicrotaskingCallToAction, MicrotaskingVolunteering, MicrotaskingDiscussion, MicrotaskingNews},
  props: {
    totalContributions: {
      type: Number,
      required: true
    },
    currentUserContributions: {
      type: Number,
      required: true
    },
    currentUserQuests: {
      type: Number,
      required: true
    },
    topics: {
      type: Array,
      required: true
    },
    seeAllTopicsLink: {
      type: String,
      required: true
    }
  },
  mounted() {
  }
}
</script>
<style scoped lang="scss">
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';
.layout {
    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;

    @include media-breakpoint-up(md) {
        grid-template-rows: auto auto;
    }
}
.top-layout {
    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;

    @include media-breakpoint-up(md) {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: auto auto;
    }
}
</style>
