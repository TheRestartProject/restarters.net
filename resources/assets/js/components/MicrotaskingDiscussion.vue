<template>
    <CollapsibleSection class="p-3 p-md-0 lineheight">
        <template slot="title">
            {{ translatedTitle }}
        </template>
        <template slot="title-right">
            <b-img class="icon ml-3 d-none d-md-block" src="/images/talk_doodle.svg" style="width:70px" />
        </template>

        <template slot="content">

            <div class="content pt-2">
            <b-table-simple v-if="isLoggedIn" responsive class="" table-class="m-0 leave-tables-alone">

                <b-thead class="text-center">
                    <b-tr class="border-0">
                    <b-th></b-th>
                    <b-th></b-th>
                    <b-th class="d-none d-md-table-cell">
                        <b-img class="icon" src="/images/speech_bubble.svg" :title="translatedNumberOfComments" />
                    </b-th>
                    <b-th class="d-none d-md-table-cell">
                        <b-img class="icon" height="28px" src="/images/clock.svg" :title="translatedTopicCreatedAt" />
                    </b-th>
                    </b-tr>
                </b-thead>
                <b-tbody v-if="isLoggedIn">
                    <DiscourseTopic
                      v-for="topic in topics"
                      :topic="topic"
                      :key="'topic' + topic.id"
                      :discourse-base-url="discourseBaseUrl"
                    />
                </b-tbody>
            </b-table-simple>
            <div class="py-4" v-else>
                Want to see the latest discussion?  <a class="btn btn-primary" href="/about">Join</a>
            </div>

            <div v-if="isLoggedIn" class="text-right pt-0 pb-2 pr-2">
                <a :href="seeAllTopicsLink">{{ translatedSeeAll }}</a>
            </div>
            </div>
        </template>
    </CollapsibleSection>
</template>

<script>
import DiscourseTopic from './DiscourseTopic'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection, DiscourseTopic},
  props: {
    topics: {
      type: Array,
      required: true
    },
    seeAllTopicsLink: {
      type: String,
      required: true
    },
    discourseBaseUrl: {
      type: String,
      required: true
    },
    isLoggedIn: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    translatedTitle() {
      return this.$lang.get('microtasking.discussion.title')
    },
    translatedNumberOfComments() {
      return this.$lang.get('microtasking.discussion.number_of_comments')
    },
    translatedTopicCreatedAt() {
      return this.$lang.get('microtasking.discussion.topic_created_at')
    },
    translatedSeeAll() {
      return this.$lang.get('microtasking.discussion.see_all')
    },
  }
}
</script>

<style scoped lang="scss">
table th {
    border: 0 !important;
}
.dashbord {
    border-top: 3px dashed black;
}
/deep/ table {
    border-collapse: separate;
    border-spacing: 0 9px;
}
.content {
    border-top: 3px dashed black;
}
.table-responsive {
    margin-bottom: 0;
}
</style>
