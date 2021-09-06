<template>
  <div>
    <VueEditor class="editor" v-model="value" :editor-options="editorOptions" />
    <input type="hidden" v-model="valueCorrected" :name="name" />
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
    initialValue: {
      type: String,
      required: false,
      default: null
    }
  },
  data: function() {
    return {
      value: null,
      valueCorrected: null,
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
    value(newVal) {
      console.log("Correct", newVal)
      // We have an odd problem on Linux where we get <p><br>.
      if (newVal) {
        newVal = newVal.replace('<p><br>', '<p>');
        console.log("Corrected", newVal)
      }

      this.valueCorrected = newVal
    }
  },
  mounted() {
    this.value = this.initialValue
    this.valueCorrected = this.initialValue
  },
  methods: {
  }
}
</script>
<style scoped lang="scss">
/deep/ .ql-editor,  /deep/ .ql-container {
  min-height: 300px !important;
  max-height: 300px !important;
  height: 300px !important;
}

/deep/ .ql-header[value="4"]::before {
  content: 'H4'
}

/deep/ .ql-header[value="5"]::before {
  content: 'H5'
}

/deep/ .ql-header[value="6"]::before {
  content: 'H6'
}

/deep/ .ql-snow .ql-editor {
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
</style>