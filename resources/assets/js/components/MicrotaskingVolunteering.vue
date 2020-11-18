<template>
    <CollapsibleSection id="volunteering" class="p-4 lineheight" :show-horizontal-rule="false">
        <template slot="title">
            Volunteer from anywhere
        </template>

        <template slot="content">
            <p class="dashbord pt-3">
                We collect data at community events.  But the raw data needs your help to become strong evidence for better products.  With your help, we can change the system.
            </p>
            <p>
               This is an easy way to make a difference, whether or not you can join us at our repair events.  Join us, and contribute as little or as much time and brain-power you can.  Most of the tasks here are OK for non-technical people.  And we're always here to help.
            </p>

            <b-table-simple sticky-header="50vh" responsive class="pl-0 pr-0 pb-2 mb-2" table-class="m-0 leave-tables-alone">
                <b-thead class="text-center">
                    <b-tr>
                    <b-th> </b-th>
                    <b-th> </b-th>
                    <b-th class="d-table-cell">
                        <b-img class="icon" src="/images/gauge_ico.svg" :title="translatedVolunteersInvited" />
                    </b-th>
                    <b-th class="d-table-cell">
                        <b-img class="icon" src="/images/thumbs-up_ico.svg" :title="translatedVolunteersConfirmed" />
                    </b-th>
                    </b-tr>
                </b-thead>

                <b-tbody class="table-height">
                    <b-tr>
                        <b-td class="text-center">
                            <b-img class="icon" src="/images/participants.svg" :title="translatedVolunteersConfirmed" />
                        </b-td>
                        <b-td>
                            Upstream volunteer contributions
                        </b-td>
                        <b-td class="text-center">{{ totalContributions.toLocaleString() }}</b-td>
                        <b-td class="text-center">3 quests</b-td>
                    </b-tr>
                    <b-tr>
                        <b-td class="text-center">
                        <b-img class="icon" src="/images/user_ico.svg" :title="translatedVolunteersConfirmed" />
                        </b-td>
                        <b-td>
                        My contributions
                        </b-td>
                        <b-td class="text-center">{{ currentUserContributions }}</b-td>
                        <b-td class="text-center">{{ currentUserQuests }} quests</b-td>
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
    }
  }
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

#volunteering {
    background-color: $white;
    border: 1px solid $black;

    @include media-breakpoint-up(md) {
        box-shadow: 5px 5px $black;
    }
}

.table td:first-child {
    padding-left: 0;
}

.dashbord {
    border-top: 3px dashed black;
}
</style>
