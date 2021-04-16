<template>
    <CollapsibleSection border-shadow class="p-4 lineheight volunteering" :show-horizontal-rule="false">
        <template slot="title">
            {{ __('microtasking.volunteering.title') }}
        </template>

        <template slot="content">
            <div v-html="__('microtasking.volunteering.content')" class="content pt-3">
            </div>

            <b-table-simple sticky-header="50vh" responsive class="pl-0 pr-0 pb-2 mb-2" table-class="m-0 leave-tables-alone">
                <b-thead class="text-center">
                    <b-tr>
                    <b-th> </b-th>
                    <b-th> </b-th>
                    <b-th class="d-table-cell">
                        <b-img class="icon" src="/images/gauge_ico.svg" :title="__('microtasking.volunteering.number_of_tasks')" />
                    </b-th>
                    <b-th class="d-table-cell">
                        <b-img class="icon" src="/images/thumbs-up_ico.svg" :title="__('microtasking.volunteering.number_of_quests')" />
                    </b-th>
                    </b-tr>
                </b-thead>

                <b-tbody class="table-height">
                    <b-tr>
                        <b-td class="text-center">
                            <b-img class="icon" src="/images/participants.svg" />
                        </b-td>
                        <b-td>
                            {{ __('microtasking.volunteering.all_volunteer_contributions') }}
                        </b-td>
                        <b-td class="text-center">{{ totalContributions.toLocaleString() }}</b-td>
                        <b-td class="text-center">{{ __('microtasking.volunteering.all_quests') }}</b-td>
                    </b-tr>
                    <b-tr>
                        <b-td class="text-center">
                        <b-img class="icon" src="/images/user_ico.svg" :title="__('groups.volunteers_confirmed')" />
                        </b-td>
                        <b-td>
                        {{ __('microtasking.volunteering.my_contributions') }}
                        </b-td>
                        <template v-if="isLoggedIn">
                            <b-td class="text-center">{{ currentUserContributions }}</b-td>
                            <b-td class="text-center">{{ translatedMyQuests }}</b-td>
                        </template>
                        <template v-else>
                            <b-td class="text-center" colspan="2"><a class="btn btn-primary" href="/about">{{ __('microtasking.volunteering.join') }}</a></b-td>
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
    translatedMyQuests() {
      return this.$lang.get('microtasking.volunteering.my_quests', { value: this.currentUserQuests })
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
