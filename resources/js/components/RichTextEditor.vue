<template>
  <div>
    <VueEditor v-model="currentValue" :editor-options="editorOptions" :class="{ 'editor': true, editorHasError: hasError }" />
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
              tags: ['a', 'b', 'strong', 'u', 's', 'i', 'p', 'br', 'ul', 'ol', 'li', 'span', 'h4', 'h5', 'h6'],
              attributes: ['href', 'rel', 'target', 'class']
            },
            keepSelection: true,
            substituteBlockElements: true,
            magicPasteLinks: true,
          },
          toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            [
              { 'header': '4' },
              { 'header': '5' },
              { 'header': '6' }
            ],
          ]
        }
      }
    }
  },
  watch: {
    currentValue: {
      handler(newVal) {
        if (newVal) {
          // We have an odd problem on Linux where we get <p><br>.
          newVal = newVal.replace('<p><br>', '<p>');
        }

        this.$emit('update:value', newVal)
      },
      immediate: true
    },
    value: {
      handler(newVal) {
        if (newVal) {
          // There's an odd problem where divs aren't handled well - see https://github.com/davidroyer/vue2-editor/issues/313.
          // This is a workaround.
          newVal = newVal.replace('<div', '<p').replace('/div>', '/p>')
        }

        this.currentValue = newVal
      },
      immediate: true
    }
  },
  mounted() {
    this.currentValue = this.value
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

::v-deep .ql-editor,  ::v-deep .ql-container {
  min-height: 300px !important;
  max-height: 300px !important;
  height: 300px !important;
}

::v-deep .ql-header[value="4"]::before {
  content: 'H4'
}

::v-deep .ql-header[value="5"]::before {
  content: 'H5'
}

::v-deep .ql-header[value="6"]::before {
  content: 'H6'
}

::v-deep .ql-snow .ql-editor {
  h4 {
    font-size: 1.5rem;
  }
  h5 {
    font-size: 1.25rem;
  }
  h6 {
    font-size: 1rem;
  }
}

.editorHasError {
  ::v-deep .ql-toolbar {
    border-top: 2px solid $brand-danger !important;
    border-left: 2px solid $brand-danger !important;
    border-right: 2px solid $brand-danger !important;
  }

  ::v-deep .ql-container {
    border-bottom: 2px solid $brand-danger !important;
    border-left: 2px solid $brand-danger !important;
    border-right: 2px solid $brand-danger !important;
  }
}
</style>