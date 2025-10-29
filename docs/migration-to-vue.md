# Migration to Vue Strategy

## Overview

Restarters.net is gradually migrating from traditional Laravel Blade templates to a modern Vue.js-based frontend architecture. This document outlines our strategy, current progress, and future vision.

## Current Architecture

The application currently uses a hybrid approach:

- **Backend**: Laravel 10 serving both traditional server-rendered pages and API endpoints
- **Frontend**: Mix of Blade templates with embedded Vue components
- **Data Flow**: Props passed from Blade templates to Vue components, with some API calls handled by Vuex stores

## Migration Strategy

### Phase 1: Gradual Component Replacement (Current)

We're replacing Blade template sections with Vue components incrementally:

1. **Identify self-contained UI sections** that can be componentized
2. **Create Vue components** that can be embedded in Blade templates
3. **Pass initial data via props** from Blade to Vue components
4. **Use Vuex stores** for state management within Vue components
5. **Keep routing in Laravel** while components handle their own interactions

**Benefits of this approach:**
- Low risk - changes are isolated
- No "big bang" rewrite
- Existing functionality continues to work
- Team can learn Vue incrementally
- Each component replacement delivers immediate value

**Current examples:**
- `GroupPage.vue` - Group detail page with member management
- `EventDevice.vue` - Device editing at repair events
- `DeviceImages.vue` - Image upload and management
- `EventActions.vue` - Event action buttons and modals

### Phase 2: API-First Development (In Progress)

As we add new features and refactor existing ones:

1. **Build API endpoints first** (see `app/Http/Controllers/API/`)
2. **Create Vue components** that consume these APIs
3. **Gradually migrate older endpoints** to RESTful API standards
4. **Standardize response formats** for consistency

**Current API coverage:**
- Events (`API/EventController.php`)
- Groups (`API/GroupController.php`)
- Devices
- Networks

### Phase 3: Full SPA Migration (Future)

Eventually, we could complete the migration to a full Single Page Application:

**Option A: Vue SPA**
- Move all routing to Vue Router
- Laravel becomes pure API server
- Vue app handles all rendering
- Use Laravel Sanctum for API authentication

**Option B: Nuxt.js**
- Server-Side Rendering (SSR) for better SEO and performance
- Laravel as headless API backend
- Nuxt client reuses existing Vue components
- Potential for static site generation for marketing pages

### Phase 4: Vue 3 Migration (Future Consideration)

Once the full SPA migration is complete or in progress, consider migrating from Vue 2 to Vue 3:

**Why Vue 3:**
- **Better Performance** - Smaller bundle sizes, faster rendering
- **Composition API** - Better code organization and reusability
- **TypeScript Support** - First-class TypeScript integration
- **Better Tree-shaking** - Smaller production builds
- **Improved Developer Experience** - Better IDE support and debugging

**Migration Path:**
1. **Preparation Phase**
   - Audit current Vue 2 components for compatibility
   - Review breaking changes in Vue 3 migration guide
   - Update build tools (Vite already supports Vue 3)
   - Test component libraries (Bootstrap Vue → BootstrapVue 3 or PrimeVue)

2. **Incremental Migration**
   - Use Vue 3 Migration Build (compatibility layer)
   - Migrate components one at a time
   - Update to Composition API gradually (can keep Options API)
   - Update Vuex to Pinia (modern Vue 3 state management)

3. **Breaking Changes to Address**
   - Filters removed (use computed properties or methods)
   - `$on`, `$off`, `$once` removed (use external event bus or props)
   - v-model syntax changes
   - Functional components syntax updated
   - Global API changes (Vue.component → app.component)

4. **Benefits for Our Application**
   - Better performance for device list rendering
   - Improved TypeScript support for API types
   - Smaller bundle sizes for better mobile experience
   - Modern tooling and community support
   - Better testability with Composition API

**Timeline Consideration:**
- Vue 2 reaches End of Life on December 31, 2023
- Vue 2 will continue to receive security updates for 18 months (until mid-2025)
- Migration should be planned before mid-2025 for continued support
- Best done after or during full SPA migration to minimize disruption

## Technical Debt to Address in Full Migration

### 1. Data Loading Patterns

**Current state:**
```javascript
// Data passed as props from Blade
this.initialGroup.idgroups = this.idgroups
this.initialGroup.canedit = this.canedit
```

**Future state:**
```javascript
// Component fetches its own data
async mounted() {
  await this.$store.dispatch('groups/fetch', this.idgroups)
}
```

### 2. Store Architecture

**Current issues:**
- Some components partially update store data
- Not all API calls go through stores
- Inconsistent patterns for loading/error states

**Future improvements:**
- All API calls via Vuex actions
- Consistent loading/error handling
- Proper cache invalidation
- Optimistic updates where appropriate

### 3. Component Props vs Computed Properties

**Current:**
```javascript
// Props for permissions that should be computed
props: ['canedit', 'candemote', 'ingroup']
```

**Future:**
```javascript
// Computed from store based on current user and group
computed: {
  canedit() {
    return this.$store.getters['groups/canEdit'](this.group, this.currentUser)
  }
}
```

### 4. jQuery Removal

**Current state:**
- jQuery still used for:
  - Select2 dropdowns
  - Bootstrap modals/tabs
  - Some DOM manipulation
  - Legacy form handling

**Future state:**
- Replace Select2 with vue-multiselect (already partially done)
- Replace Bootstrap jQuery components with Bootstrap Vue or custom components
- Remove jQuery dependency entirely

### 5. Form Handling

**Current patterns:**
- Mix of traditional form submissions and AJAX
- Validation split between server and client
- Inconsistent error display

**Future patterns:**
- All forms as Vue components
- Vuelidate for client-side validation (already included)
- Consistent Laravel validation on API
- Standardized error handling and display

## Migration Checklist for New Features

When adding new features, follow these guidelines:

- [ ] Create API endpoint first if one doesn't exist
- [ ] Use RESTful conventions (GET, POST, PUT, DELETE)
- [ ] Return consistent JSON response format
- [ ] Create Vuex store module if needed
- [ ] Build Vue component instead of Blade template
- [ ] Handle loading and error states
- [ ] Add client-side validation with Vuelidate
- [ ] Keep server-side validation as source of truth
- [ ] Write API tests for new endpoints
- [ ] Document API in OpenAPI/Swagger

## Files to Review During Full Migration

### Can be removed after full migration:
- `resources/views/**/*.blade.php` (most of them)
- jQuery-dependent JavaScript in `resources/js/app.js`
- Bootstrap 4 jQuery plugins
- Server-side route definitions for view rendering (keep API routes)

### Will need significant refactoring:
- `app/Http/Controllers/*Controller.php` - Convert to API controllers
- `routes/web.php` - Replace with API routes
- Authentication middleware - Update for SPA/API auth
- CSRF handling - Move to API token approach

### Can be reused as-is or with minor changes:
- `resources/js/components/**/*.vue` - All Vue components
- `resources/js/store/**/*.js` - Vuex store modules
- API controllers in `app/Http/Controllers/API/`
- Models in `app/`
- Services in `app/Services/`
- All business logic

## Benefits of Full Migration

1. **Better Developer Experience**
   - Single-page app navigation without full page reloads
   - Hot module replacement for instant feedback
   - Modern tooling and debugging

2. **Better User Experience**
   - Faster navigation after initial load
   - Optimistic updates
   - Consistent loading states
   - Better mobile experience

3. **Better Architecture**
   - Clear separation of concerns (API backend, Vue frontend)
   - API can be consumed by mobile apps or third parties
   - Easier testing of frontend and backend separately
   - Modern best practices

4. **Better Performance**
   - Code splitting for smaller initial bundles
   - Lazy loading of components
   - Client-side caching
   - Optional SSR with Nuxt for initial load performance

## Timeline Considerations

**Short term (Current):**
- Continue incremental Vue component replacement
- Focus on high-traffic pages first
- Build all new features with Vue components

**Medium term (6-12 months):**
- Complete API coverage for all major features
- Migrate most common user journeys to Vue
- Remove jQuery dependencies
- Standardize authentication approach

**Long term (12+ months):**
- Evaluate readiness for full SPA migration
- Consider Nuxt.js for SSR benefits
- Plan migration of remaining pages
- Update deployment strategy for SPA

## Questions to Answer Before Full Migration

1. **SEO Requirements**: Do we need SSR for search engine optimization?
2. **Browser Support**: Can we drop IE11 and older browsers?
3. **Authentication**: Move to token-based auth (Sanctum) or keep sessions?
4. **Deployment**: How will deployment change? CDN for static assets?
5. **Testing**: Update testing strategy for SPA?
6. **Analytics**: Update analytics tracking for client-side routing?

## Resources

- Vue 2 Documentation: https://v2.vuejs.org/
- Vuex Documentation: https://v3.vuex.vuejs.org/
- Nuxt.js Documentation: https://nuxtjs.org/
- Laravel Sanctum: https://laravel.com/docs/10.x/sanctum
- Vue Router: https://router.vuejs.org/

## Contributing

When working on Vue components:
- Follow the existing patterns in `resources/js/components/`
- Use Vuex stores for shared state
- Use Vuelidate for form validation
- Include loading and error states
- Test components in isolation where possible
- Document complex components with JSDoc comments
