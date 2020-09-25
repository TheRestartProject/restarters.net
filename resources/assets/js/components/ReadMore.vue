<template>
  <div>
    <p v-html="formattedString"></p>
    <span v-show="text.length > maxChars">
			<a :href="link" id="readmore" v-if="!isReadMore" v-on:click="triggerReadMore($event, true)" v-html="moreStr" class="d-flex justify-content-center"/>
			<a :href="link" id="readmore" v-if="isReadMore" v-on:click="triggerReadMore($event, false)" v-html="lessStr" class="d-flex justify-content-center" />
		</span>
  </div>
</template>

<script>
// Originally based on https://github.com/orlyyani/read-more, with thanks.

export default {
  props: {
    moreStr: {
      type: String,
      default: "read more"
    },
    lessStr: {
      type: String,
      default: ""
    },
    text: {
      type: String,
      required: true
    },
    link: {
      type: String,
      default: "#"
    },
    maxChars: {
      type: Number,
      default: 100
    }
  },

  data() {
    return {
      isReadMore: false
    };
  },

  computed: {
    formattedString() {
      var val_container = this.text;

      if (!this.isReadMore && this.text.length > this.maxChars) {
        val_container = val_container.substring(0, this.maxChars) + "...";
      }

      return val_container;
    }
  },

  methods: {
    triggerReadMore(e, b) {
      if (this.link == "#") {
        e.preventDefault();
      }
      if (this.lessStr !== null || this.lessStr !== "") this.isReadMore = b;
    }
  }
};
</script>