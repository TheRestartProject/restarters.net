<template>
    <div id="news">
        <div class="d-none d-sm-block text-center">
            <b-img id="news-image" src="/images/mobifix-treemap.png" />
        </div>
    <CollapsibleSection class="collapsible px-4 pb-4 pt-0 lineheight" :show-horizontal-rule="false">

        <template slot="title">
            <div class="mt-2">
            {{ __('microtasking.news.title') }}
            </div>
        </template>

        <template slot="title-right">
            <b-img class="d-none d-sm-block ml-auto mt-2 doodle" src="/images/phone.svg" />
        </template>

        <template slot="content">
            <div v-html="__('microtasking.news.content')" class="pt-3 content" />
        </template>
    </CollapsibleSection>
    </div>
</template>

<script>
import CollapsibleSection from './CollapsibleSection'

export default {
    components: {CollapsibleSection},
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

#news-image {
    width: 400px;
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

    ::v-deep a {
        color: $black;
        text-decoration: underline !important;

        &:hover {
            text-decoration: none !important;
        }
    }

    ::v-deep ul {
        list-style-image: url('/images/arrow-right-doodle.svg');
    }
}

.doodle {
    background-size: auto 75px;
    height: 75px;
}


</style>
</template>
