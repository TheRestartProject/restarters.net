<template>
    <div id="news">
        <b-img src="https://placekitten.com/395/435/" />
    <CollapsibleSection class="collapsible px-4 pb-4 pt-0 lineheight" :show-horizontal-rule="false">

        <template slot="title">
            Read about our recent success
        </template>

        <template slot="title-right">
            <b-img class="ml-auto mt-2 doodle" src="/images/laptop.svg" />
        </template>

        <template slot="content">
            <div class="content">
                <p class="dashbord pt-3">
                    Through our FaultCat quest, hundreds of volunteers helped us analyse our laptop data.

                    <ul>
                        <li><a href="">Read the results</a> as they went into this policy paper on laptop lifetimes.</li>
                        <li>
                            <a href="">Check out the data visualisation</a> featured in The Times newspaper.
                        </li>
                    </ul>
                </p>
            </div>
        </template>
    </CollapsibleSection>
    </div>
</template>

<script>
import CollapsibleSection from './CollapsibleSection'

export default {
    components: {CollapsibleSection},
    props: {
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

.content {
    border-top: 3px dashed black;

    a {
        color: $black;
        text-decoration: underline !important;

        &:hover {
            text-decoration: none !important;
        }
    }

    ul {
        list-style-image: url('/images/arrow-right-doodle.svg');
    }
}

.doodle {
    background-size: auto 75px;
    height: 75px;
}


</style>
</template>
