<template>
  <div>
    <VueEditor class="editor" v-model="value" :editor-options="editorOptions" />
    <input type="hidden" v-model="value" :name="name" />
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
      editorOptions: {
        modules: {
          htmlEditButton: {},
          clipboard: {
            allowed: {
              tags: ['a', 'b', 'strong', 'u', 's', 'i', 'p', 'br', 'ul', 'ol', 'li', 'span'],
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
            ['link']
          ]
        }
      }
    }
  },
  mounted() {
    this.value = this.initialValue
  },
  methods: {
  }
}
</script>
<style scoped lang="scss">
.editor {
  height: 300px;
}
</style>