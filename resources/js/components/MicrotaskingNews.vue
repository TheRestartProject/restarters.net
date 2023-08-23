<template>
    <div id="news">
      <CollapsibleSection class="collapsible px-4 pb-4 pt-0 lineheight" :show-horizontal-rule="false">

          <template slot="title">
              <div class="mt-2">
              {{ __('microtasking.news.title') }}
              </div>
          </template>

          <template slot="title-right">
          </template>

          <template slot="content">
            <div class="d-flex justify-content-lg-between flex-column flex-lg-row content pt-4">
              <div class="embed-responsive embed-responsive-16by9 mr-4 w-100 w-lg-50">
                <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/svnC5QPK9mY"></iframe>
              </div>
              <div v-html="__('microtasking.news.content')" class="pt-3 w-100 w-lg-50" />
            </div>
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
