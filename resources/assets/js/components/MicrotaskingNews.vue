<template>
    <div id="news">
        <div class="d-none d-sm-block text-center">
            <b-img src="https://placekitten.com/395/435/" />
        </div>
    <CollapsibleSection class="collapsible px-4 pb-4 pt-0 lineheight" :show-horizontal-rule="false">

        <template slot="title">
            <div class="mt-2">
            {{ translatedTitle }}
            </div>
        </template>

        <template slot="title-right">
            <b-img class="d-none d-sm-block ml-auto mt-2 doodle" src="/images/laptop.svg" />
        </template>

        <template slot="content">
            <div v-html="translatedContent" class="pt-3 content" />
        </template>
    </CollapsibleSection>
    </div>
</template>

<script>
import CollapsibleSection from './CollapsibleSection'

export default {
    components: {CollapsibleSection},
    computed: {
      translatedTitle() {
        return this.$lang.get('microtasking.news.title')
      },
      translatedContent() {
        return this.$lang.get('microtasking.news.content')
      }
    },
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

#news {
    border: 1px solid $black;

    @include media-breakpoint-up(md) {
        box-shadow: 5px 5px $black;
    }
}

.collapsible {
    background-color: #ffbe5f;
}

a {
    color: $black;
    text-decoration: underline !important;

    &:hover {
        text-decoration: none !important;
    }
}

.content {
    border-top: 3px dashed black;

    /deep/ a {
        color: $black;
        text-decoration: underline !important;

        &:hover {
            text-decoration: none !important;
        }
    }

    /deep/ ul {
        list-style-image: url('/images/arrow-right-doodle.svg');
    }
}

.doodle {
    background-size: auto 75px;
    height: 75px;
}


</style>
</template>
