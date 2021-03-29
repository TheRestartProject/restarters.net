<template>
    <CollapsibleSection border-shadow class="p-4 lineheight volunteering" :show-horizontal-rule="false">
        <template slot="title">
            {{ translatedTitle }}
        </template>

        <template slot="content">
            <div v-html="translatedContent" class="content pt-3">
            </div>

            <b-table-simple sticky-header="50vh" responsive class="pl-0 pr-0 pb-2 mb-2" table-class="m-0 leave-tables-alone">
                <b-thead class="text-center">
                    <b-tr>
                    <b-th> </b-th>
                    <b-th> </b-th>
                    <b-th class="d-table-cell">
                        <b-img class="icon" src="/images/gauge_ico.svg" :title="translatedNumberOfTasks" />
                    </b-th>
                    <b-th class="d-table-cell">
                        <b-img class="icon" src="/images/thumbs-up_ico.svg" :title="translatedNumberOfQuests" />
                    </b-th>
                    </b-tr>
                </b-thead>

                <b-tbody class="table-height">
                    <b-tr>
                        <b-td class="text-center">
                            <b-img class="icon" src="/images/participants.svg" />
                        </b-td>
                        <b-td>
                            {{ translatedAllContributions }}
                        </b-td>
                        <b-td class="text-center">{{ totalContributions.toLocaleString() }}</b-td>
                        <b-td class="text-center">{{ translatedAllQuests }}</b-td>
                    </b-tr>
                    <b-tr>
                        <b-td class="text-center">
                        <b-img class="icon" src="/images/user_ico.svg" :title="translatedVolunteersConfirmed" />
                        </b-td>
                        <b-td>
                        {{ translatedMyContributions }}
                        </b-td>
                        <template v-if="isLoggedIn">
                            <b-td class="text-center">{{ currentUserContributions }}</b-td>
                            <b-td class="text-center">{{ translatedMyQuests }}</b-td>
                        </template>
                        <template v-else>
                            <b-td class="text-center" colspan="2"><a class="btn btn-primary" href="/about">{{ translatedJoin }}</a></b-td>
                        </template>
                    </b-tr>
                </b-tbody>
            </b-table-simple>

        </template>
    </CollapsibleSection>
</template>

<script>
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection},
  props: {
    totalContributions: {
      type: Number,
      required: true
    },
    currentUserQuests: {
      type: Number,
      required: true
    },
    currentUserContributions: {
      type: Number,
      required: true
    },
    isLoggedIn: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    translatedTitle() {
      return this.$lang.get('microtasking.volunteering.title')
    },
    translatedContent() {
      return this.$lang.get('microtasking.volunteering.content')
    },
    translatedNumberOfTasks() {
      return this.$lang.get('microtasking.volunteering.number_of_tasks')
    },
    translatedNumberOfQuests() {
      return this.$lang.get('microtasking.volunteering.number_of_quests')
    },
    translatedAllContributions() {
      return this.$lang.get('microtasking.volunteering.all_volunteer_contributions')
    },
    translatedAllQuests() {
      return this.$lang.get('microtasking.volunteering.all_quests')
    },
    translatedMyContributions() {
      return this.$lang.get('microtasking.volunteering.my_contributions')
    },
    translatedMyQuests() {
      return this.$lang.get('microtasking.volunteering.my_quests', { value: this.currentUserQuests })
    },
    translatedJoin() {
      return this.$lang.get('microtasking.volunteering.join')
    },
    translatedVolunteersConfirmed() {
      return this.$lang.get('groups.volunteers_confirmed')
    },
  }
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.volunteering {
    background-color: $white;
    border: 1px solid $black;

    @include media-breakpoint-up(md) {
        box-shadow: 5px 5px $black;
    }
}

.table td:first-child {
    padding-left: 0;
}

.content {
    border-top: 3px dashed black;
}
</style>
