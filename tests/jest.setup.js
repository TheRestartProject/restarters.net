// Mock import.meta.env for Vite compatibility
Object.defineProperty(globalThis, 'import', {
  value: {
    meta: {
      env: {
        VITE_LARAVEL_TRANSLATIONS: {}
      }
    }
  }
});