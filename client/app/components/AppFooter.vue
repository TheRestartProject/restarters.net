<script setup>
import LocaleSwitcher from '~/components/LocaleSwitcher.vue'

// layouts/app.blade.php ends with `@include('partials.languages')` followed by
// `@include('layouts/footer')`, and layouts/footer.blade.php is literally
// `<footer></footer>` - so the only thing below the page content is the
// language bar. The Help & Feedback / FAQs / The Restart Project links live in
// the navbar's "Other" menu (layouts/navbar.blade.php:177-183), not here.
</script>

<template>
  <!-- The bar itself (rule, background, right alignment) is LocaleSwitcher's
       own .language-bar root, as live's partials.languages is. Giving this
       wrapper that class too drew two rules with padding between them. -->
  <footer data-testid="app-language-bar">
    <LocaleSwitcher />
  </footer>
</template>
