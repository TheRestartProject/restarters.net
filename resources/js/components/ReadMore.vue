<template>
  <div>
    <span v-if="text">
      <p v-html="formattedString"></p>
    </span>
    <span v-else-if="html">
      <span v-if="!needsTruncating" v-html="html" class="w-100" />
      <span v-else>
        <span v-if="!isReadMore" v-html="truncatedHTML" class="w-100" />
        <span v-else v-html="html" class="w-100" />
      </span>
    </span>
    <span v-if="needsTruncating">
      <a :href="link" id="readmore" v-if="!isReadMore" v-on:click="triggerReadMore($event, true)" v-html="moreStr" class="d-flex justify-content-center"/>
      <a :href="link" id="readmore" v-if="isReadMore" v-on:click="triggerReadMore($event, false)" v-html="lessStr" class="d-flex justify-content-center" />
    </span>
  </div>
</template>

<script>
import { htmlToText } from 'html-to-text';
import clip from "text-clipper"
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
    },
    maxLines: {
      type: Number,
      default: 10
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
      const ret = this.html ? clip(this.html, this.maxChars, { html: true, maxLines: this.maxLines }) : null
      return ret
    },
    needsTruncating() {
      if (this.text && (text.length > maxChars)) {
        return true
      }

      // For HTML we need to do a more complex check, as truncate() can result in HTML which is different from
      // the original even if it's not removed anything, because of slight HTML differences.
      const origtext = htmlToText(this.html)
      const truncatedtext = htmlToText(this.truncatedHTML)

      return origtext !== truncatedtext
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