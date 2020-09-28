<template>
  <div>
    <span v-if="text">
      <p v-html="formattedString"></p>
    </span>
    <span v-else-if="html">
      <span v-if="!needsTruncating" v-html="html" />
      <span v-else>
        <span v-html="truncatedHTML" />
        <span v-if="isReadMore">...</span>
      </span>
    </span>
    <span v-if="needsTruncating">
      <a :href="link" id="readmore" v-if="!isReadMore" v-on:click="triggerReadMore($event, true)" v-html="moreStr" class="d-flex justify-content-center"/>
      <a :href="link" id="readmore" v-if="isReadMore" v-on:click="triggerReadMore($event, false)" v-html="lessStr" class="d-flex justify-content-center" />
    </span>
  </div>
</template>

<script>
const truncate = require('html-truncate');
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
      required: false
    },
    html: {
      type: String,
      required: false
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
    },
    truncatedHTML() {
      // We need to truncate HTML with care to ensure that the result is tag safe; string truncation isn't good
      // enough.
      return this.html ? truncate(this.html, !this.isReadMore ? this.maxChars : 100000) : null
    },
    untruncatedHTML() {
      return this.html ? truncate(this.html, this.maxChars) : this.html
    },
    needsTruncating() {
      const ret = (this.text && text.length > maxChars) || (this.html && this.truncatedHTML !== this.html)
      console.log("Needs", ret, this.maxChars, this.truncatedHTML !== this.untruncatedHTML, this.truncatedHTML, this.untruncatedHTML)
      return ret
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