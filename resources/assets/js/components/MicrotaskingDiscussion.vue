<template>
    <CollapsibleSection class="py-4 lineheight">
        <template slot="title">
            What's happening
        </template>
        <template slot="title-right">
            foo
        </template>

        <template slot="content">
            <div class="content">
            <b-table-simple responsive class="" table-class="m-0 leave-tables-alone">

                <b-thead class="text-center">
                    <b-tr class="border-0">
                    <b-th></b-th>
                    <b-th></b-th>
                    <b-th class="d-none d-md-table-cell">
                        <b-img class="icon" src="/images/speech_bubble.svg" :title="translatedVolunteersInvited" />
                    </b-th>
                    <b-th class="d-none d-md-table-cell">
                        <b-img class="icon" height="28px" src="/images/clock.svg" :title="translatedVolunteersInvited" />
                    </b-th>
                    </b-tr>
                </b-thead>
                <b-tbody>
                    <DiscourseTopic v-for="topic in topics" :topic="topic" :key="'topic' + topic.id" />
                </b-tbody>
            </b-table-simple>

            </b-table-simple>
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
.content {
    border-top: 3px dashed black;
}
</style>
