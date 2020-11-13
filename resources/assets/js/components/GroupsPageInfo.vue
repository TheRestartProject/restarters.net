<template>
  <div>
    <div class="d-block d-md-none p-3">
      <read-more :html="mobileIntro" :max-lines="1" :more-str="translatedReadMore" :less-str="translatedReadLess" />
    </div>
    <div class="d-none d-md-block">
      <div class="split">
        <div class="p-3">
          <b>
            {{ translatedWeAre }}
          </b>
        </div>
        <div class="p-3 bord">
          {{ translatedQuickAccess }}
        </div>
        <div class="p-3">
          {{ translatedWhyNot1 }}
          <a href="#" @click="$emit('nearest')">
            {{ translatedWhyNot2}}
          </a>
          {{ translatedWhyNot3 }}
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import ReadMore from './ReadMore'
export default {
  components: {ReadMore},
  computed: {
    mobileIntro() {
      // A bit poor to construct HTML like this, but we want to pass it to v-html and the security risk is
      // acceptable because of access restrictions on who can edit this.
      return '<p><b>' + this.translatedWeAre + '</b></p>' +
          '<p>' + this.translatedQuickAccess + '</p>' +
          '<p>' + this.translatedWhyNot1 + ' <a href="/group/nearby">' + this.translatedWhyNot2 + '</a> ' + this.translatedWhyNot3 + '</p>'
    },
    translatedWeAre() {
      return this.$lang.get('groups.introduction')
    },
    translatedQuickAccess() {
      return this.$lang.get('groups.quick_access')
    },
    translatedWhyNot1() {
      return this.$lang.get('groups.why_not1')
    },
    translatedWhyNot2() {
      return this.$lang.get('groups.why_not2')
    },
    translatedWhyNot3() {
      return this.$lang.get('groups.why_not3')
    },
    translatedReadMore() {
      return this.$lang.get('events.read_more')
    },
    translatedReadLess() {
      return this.$lang.get('events.read_less')
    }
  }
}
</script>
<style scoped lang="scss">

.split {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
}

.bord {
  border-left: 1px dashed black;
  border-right: 1px dashed black;
}
</style>