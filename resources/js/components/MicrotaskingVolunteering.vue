<template>
    <CollapsibleSection border-shadow class="p-4 lineheight volunteering" :show-horizontal-rule="false">
        <template slot="title">
            {{ __('microtasking.volunteering.title') }}
        </template>

        <template slot="content">
            <div v-html="__('microtasking.volunteering.content')" class="content pt-3">
            </div>
            <hr>

            <div v-if="openQuests.length">
            <h2>{{ __('microtasking.volunteering.open_quests') }}</h2>

            <div class="open-quests">
                <div class="open-quest" v-for="quest in openQuests">
                    <h3>{{ quest.name }} {{ quest.emoji }}</h3>
                    <div class="open-quest-body">
                        <p>{{ quest.shortintro }}</p>
                        <div class="try-open-quest">
                            <a :href="quest.slug" class="btn btn-primary btn-open-quest" >{{__('microtasking.volunteering.try_quest', { questname: quest.name })}}</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            </div>

            <b-table-simple sticky-header="50vh" responsive class="pl-0 pr-0 pb-2 mb-2 bg-white p-2" table-class="m-0 leave-tables-alone">
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
                        <b-td class="text-center">{{ __('microtasking.volunteering.all_quests', { value: totalQuests }) }}</b-td>
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
                            <b-td class="text-center">{{ __('microtasking.volunteering.my_quests', { value: currentUserQuests }) }}</b-td>
                        </template>
                        <template v-else>
                            <b-td class="text-center" colspan="2"><a class="btn btn-primary" href="/user/register">{{ __('microtasking.volunteering.join') }}</a></b-td>
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
    totalQuests: {
      type: Number,
      required: true
    },
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
    openQuests: function () {
      // Note: if we continue with more quests, and open/closed quests,
      // we will likely have a 'quests' table.
      return []
    }
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

::v-deep .open-quest {
    margin-top: 30px;
}

::v-deep h3 {
    font-size: 1.1em;
    font-weight: bold;
}

::v-deep .open-quest-body {
    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;

    @include media-breakpoint-up(md) {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: 1fr;
    }

    .try-open-quest {
        place-self: start right;

        @include media-breakpoint-up(md) {
            width: 100%;
            display: block;
        }

        a {
            width: auto;
            display: block;
            align-self: center;
        }
    }
}

.table td:first-child {
    padding-left: 0;
}

.content {
    border-top: 3px dashed black;
}

.volunteering {
  background-color: #ffbe5f;
}
</style>
