<template>
    <CollapsibleSection class="py-4 lineheight">
        <template slot="title">
            What's happening
        </template>

        <template slot="content">
            <b-table-simple responsive class="" table-class="m-0 leave-tables-alone">

                <b-thead class="text-center">
                    <b-tr class="border-0">
                    <b-th></b-th>
                    <b-th></b-th>
                    <b-th class="d-none d-md-table-cell">
                        <b-img class="icon" src="/images/mail_ico.svg" :title="translatedVolunteersInvited" />
                    </b-th>
                    <b-th class="d-none d-md-table-cell">
                        <b-img class="icon" src="/images/mail_ico.svg" :title="translatedVolunteersInvited" />
                    </b-th>
                    </b-tr>
                </b-thead>
                <b-tbody>
                    <MicrotaskingDiscussionTopic v-for="topic in topics" :topic="topic" :key="'topic' + topic.id" />
                </b-tbody>
            </b-table-simple>

            </b-table-simple>
        </template>
    </CollapsibleSection>
</template>

<script>
import MicrotaskingDiscussionTopic from './MicrotaskingDiscussionTopic'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection, MicrotaskingDiscussionTopic},
  props: {
    'topics': {
      type: Array,
      required: true
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
/deep/ table {
    border-collapse: separate;
    border-spacing: 0 9px;
}
</style>
