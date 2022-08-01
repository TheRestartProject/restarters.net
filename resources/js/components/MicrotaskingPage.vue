<template>
<div>
    <h1 class="d-flex justify-content-between">
        <div class="d-flex">
            <div class="mt-2">
                {{ __('microtasking.title') }}
            </div>
            <b-img id="doodle" class="height ml-4" src="/images/workbench_doodle.svg" />
        </div>
    </h1>

    <div id="layout">
        <MicrotaskingCallToAction
          id="cta"
          v-if="showCta"
          :active-quest="activeQuest"
        />
        <MicrotaskingVolunteering
          :total-quests="totalQuests"
          :total-contributions="totalContributions"
          :current-user-quests="currentUserQuests"
          :current-user-contributions="currentUserContributions"
          :is-logged-in="isLoggedIn"
        />
        <DiscourseDiscussion
          id="discussion"
          :tag="tag"
          :see-all-topics-link="seeAllTopicsLink"
          :discourse-base-url="discourseBaseUrl"
          :is-logged-in="isLoggedIn"
        />
        <MicrotaskingNews id="news" />
    </div>
</div>
</template>

<script>
import MicrotaskingCallToAction from './MicrotaskingCallToAction'
import MicrotaskingVolunteering from './MicrotaskingVolunteering'
import MicrotaskingNews from './MicrotaskingNews'
import auth from '../mixins/auth'
import DiscourseDiscussion from './DiscourseDiscussion'

export default {
  components: {MicrotaskingCallToAction, MicrotaskingVolunteering, DiscourseDiscussion, MicrotaskingNews},
  mixins: [ auth ],
  props: {
    activeQuest: {
      type: String,
      required: true
    },
    totalQuests: {
      type: Number,
      required: true
    },
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
    tag: {
      type: String,
      required: true
    },
    seeAllTopicsLink: {
      type: String,
      required: true
    },
    isLoggedIn: {
      type: Boolean,
      required: true
    },
    discourseBaseUrl: {
      type: String,
      required: true
    }
  },
  computed: {
    showCta() {
      return true
    },
  }
}
</script>

<style scoped lang="scss">
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

#layout {
    display: grid;

    grid-template-columns: 1fr;
    grid-gap: 1.5em;

    @include media-breakpoint-up(md) {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: auto auto auto;

        #cta {
            grid-area: 1 / 1 / 2 / 2;
            align-self: start;
        }

        #volunteering {
            grid-area: 2 / 1 / 3 / 2;
        }

        #news {
            grid-area: 1 / 2 / 3 / 3;
            align-self: start;
        }

        #discussion {
            grid-area: 3 / 1 / 4 / 3;
        }
    }
}

#doodle {
    height: 75px;
}
</style>
