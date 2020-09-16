export default {
  methods: {
    pluralise(str, val) {
      // Laravel blade templates allow pluralisation, the simplest case being of the form singular|plural.
      // lang.js doesn't have that, so do the simple case ourselves.
      const p = str.indexOf('|')
      val = parseInt(val)

      if (p !== -1) {
        if (val === 1) {
          str = str.substring(0, p)
        } else {
          str = str.substring(p + 1)
        }
      }

      return str
    }
  }
}