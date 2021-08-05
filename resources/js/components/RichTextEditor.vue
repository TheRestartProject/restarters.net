<template>
  <div>
    <VueEditor class="editor" v-model="currentValue" :editor-options="editorOptions" :class="{ editorHasError: hasError }" />
    <input type="hidden" v-model="currentValue" :name="name" />
  </div>
</template>
<script>
const Quill = require('vue2-editor').Quill
window.Quill = Quill
const htmlEditButton = require('quill-html-edit-button').htmlEditButton
const VueEditor = require('vue2-editor').VueEditor
Quill.register('modules/htmlEditButton', htmlEditButton)

// Importing this registers a clipboard handler that sanitizes on paste.
import 'quill-paste-smart'

export default {
  components: {
    VueEditor,
  },
  props: {
    name: {
      type: String,
      required: true
    },
    value: {
      type: String,
      required: false,
      default: null
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data: function() {
    return {
      currentValue: null,
      editorOptions: {
        modules: {
          htmlEditButton: {},
          clipboard: {
            allowed: {
              tags: ['a', 'b', 'strong', 'u', 's', 'i', 'p', 'br', 'ul', 'ol', 'li', 'span', 'h4'],
              attributes: ['href', 'rel', 'target', 'class']
            },
            keepSelection: true,
            substituteBlockElements: true,
            magicPasteLinks: true,
          },
          toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'align': [] }],
            ['link'],
            [{ 'header': '4' }],
          ]
        }
      }
    }
  },
  mounted() {
    this.currentValue = this.value
  },
  watch: {
    currentValue(newVal) {
      this.$emit('update:value', newVal)
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

/deep/ .ql-editor,  /deep/ .ql-container {
  min-height: 300px !important;
  max-height: 300px !important;
  height: 300px !important;
}

/deep/ .ql-header::before {
  content: 'H4'
}

/deep/ .ql-header {
  white-space: nowrap;
}

.editorHasError {
  /deep/ .ql-toolbar {
    border-top: 2px solid $brand-danger !important;
    border-left: 2px solid $brand-danger !important;
    border-right: 2px solid $brand-danger !important;
  }

  /deep/ .ql-container {
    border-bottom: 2px solid $brand-danger !important;
    border-left: 2px solid $brand-danger !important;
    border-right: 2px solid $brand-danger !important;
  }
}

/deep/ .ql-toolbar {
  border-top: 2px solid $black !important;
  border-left: 2px solid $black !important;
  border-right: 2px solid $black !important;
}

/deep/ .ql-container {
  border-bottom: 2px solid $black !important;
  border-left: 2px solid $black !important;
  border-right: 2px solid $black !important;
}

/deep/ .ql-container.ql-snow {
  border: unset;
}

</style>