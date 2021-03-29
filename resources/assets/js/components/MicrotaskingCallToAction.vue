<template>
    <CollapsibleSection id="cta" class="px-4 pb-4 pt-4 pt-md-0 lineheight" :show-horizontal-rule="false">
        <template slot="title">
            {{ translatedTitle }}
        </template>

        <template slot="title-right">
            <b-img class="d-none d-sm-block ml-auto doodle" src="/images/wire-strippers.svg" />
        </template>

        <template slot="content">
            <div class="content pt-3">
                <div v-html="translatedDescription">
                </div>
                <div style="align-self:center; justify-self:right">
                <a href="/mobifixora" style="align-self: center" class="btn btn-primary pull-right">{{ translatedGetInvolved }}</a>
                </div>
            </div>

        </template>
    </CollapsibleSection>
</template>

<script>
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection},
  computed: {
    translatedTitle() {
      return this.$lang.get('microtasking.cta.title')
    },
    translatedDescription() {
      return this.$lang.get('microtasking.cta.description')
    },
    translatedGetInvolved() {
      return this.$lang.get('microtasking.cta.get_involved')
    },
  }
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

#cta {
    background-color: $brand-light;
    border: 1px solid $black;

    @include media-breakpoint-up(md) {
        box-shadow: 5px 5px $black;
    }
}

.content {
    border-top: 3px dashed black;

    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;

    @include media-breakpoint-up(md) {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: 1fr;
    }
}

.doodle {
    background-size: auto 75px;
    height: 75px;
}
</style>
