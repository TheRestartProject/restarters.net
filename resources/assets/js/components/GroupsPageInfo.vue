<template>
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
      <a href="#">
        {{ translatedWhyNot2}}
      </a>
      {{ translatedWhyNot3 }}
<!--      TODO Link to tab-->
    </div>
  </div>
</template>
<script>
export default {
  computed: {
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