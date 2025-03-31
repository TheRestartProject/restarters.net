<template>
    <CollapsibleSection class="pt-3 p-md-0 lineheight">
    <template slot="title">
          <div class="d-flex">
      {{ __('discourse.title') }}
      <b-img class="icon ml-3 d-none d-md-block" src="/images/talk_doodle.svg" style="width:70px"/>
          </div>
    </template>

    <template slot="content">

      <div class="content pt-2">
        <b-table-simple responsive class="" table-class="m-0 leave-tables-alone">
          <b-thead class="text-center">
            <b-tr class="border-0">
              <b-th></b-th>
              <b-th class="d-none d-md-table-cell">
                <b-img class="icon" src="/images/speech_bubble.svg" :title="__('discourse.number_of_comments')"/>
              </b-th>
              <b-th class="d-none d-md-table-cell">
                <b-img class="icon" height="28px" src="/images/clock.svg" :title="__('discourse.topic_created_at')"/>
              </b-th>
            </b-tr>
          </b-thead>
          <b-tbody v-if="topics">
            <DiscourseTopic
                v-for="topic in topics"
                :topic="topic"
                :key="'topic' + topic.id"
                :discourse-base-url="discourseBaseUrl"
            />
          </b-tbody>
        </b-table-simple>

        <div class="text-right pt-0 pb-2 pr-2">
          <a :href="seeAllTopicsLink">{{ __('discourse.see_all') }}</a>
        </div>
      </div>
    </template>
  </CollapsibleSection>
</template>

<script>
import DiscourseTopic from './DiscourseTopic'
import CollapsibleSection from './CollapsibleSection'
const axios = require('axios')

export default {
  components: {CollapsibleSection, DiscourseTopic},
  props: {
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
    },
    tag: {
      type: String,
      required: false,
      default: null
    }
  },
  data () {
    return {
      topics: []
    }
  },
  async mounted() {
    const ret = await axios.get(this.tag ? ('/api/talk/topics/' + this.tag) : '/api/talk/topics')

    if (ret.data.success) {
      this.topics = ret.data.topics
    }
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
::v-deep table {
    border-collapse: separate;
    border-spacing: 0 9px;
}
.content {
    border-top: 3px dashed black;
}
.table-responsive {
    margin-bottom: 0;
}

a {
  text-decoration: underline;
  color: #222;
}
</style>
