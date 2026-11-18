import { defineStore } from 'pinia'

/**
 * Backs the five admin reference-data pages (design.md §6.2 Phase D task D4;
 * PR #863's AdminCrudPage.vue + BrandsPage/SkillsPage/GroupTagsPage/
 * CategoriesPage/RolesPage.vue are the functional spec):
 * /brands, /skills, /tags, /category, /role.
 *
 * All five APIs (BrandController/SkillController/GroupTagController/
 * CategoryController/RoleController) are already implemented server-side
 * (confirmed by reading routes/api.php + each controller directly). List/get
 * are public everywhere; every mutation requires Administrator (403 JSON
 * otherwise - enforced server-side; page-level gating is UX-level only,
 * design.md §4.4).
 *
 * Each list section keeps itself alphabetically sorted client-side after
 * every create/update, mirroring the legacy AdminCrudPage.vue's
 * `sortItems` prop (which every PR-863 page passed a `localeCompare` sort
 * for) - the server does the same for the initial GET (`orderBy(...,
 * 'asc')`), but a freshly-created/renamed row needs re-sorting into place
 * client-side without a full re-fetch.
 *
 * Categories is list+update ONLY (no create/delete route exists
 * server-side - see api/CategoryAPI.js) and additionally carries
 * `clusters` (GET /api/v2/category-clusters, for the edit form's cluster
 * dropdown - a static reference list, fetched once).
 *
 * Roles is read-mostly: `updateRolePermissions` replaces a role's granted
 * permission set (not a generic create/update/delete resource - roles
 * cannot be created, renamed or deleted, only their permissions edited),
 * and `permissions` is the full permission catalogue for the edit modal's
 * checkbox matrix. pages/role.vue is bespoke rather than built on
 * AdminCrudTable.vue for this reason - see that page's own doc comment.
 */
export const useAdminRefdataStore = defineStore('adminRefdata', {
  state: () => ({
    brands: { data: [], loading: false, error: null },
    skills: { data: [], loading: false, error: null },
    groupTags: { data: [], loading: false, error: null },
    categories: { data: [], loading: false, error: null },
    clusters: { data: [], loading: false, error: null },
    roles: { data: [], loading: false, error: null },
    permissions: { data: [], loading: false, error: null },
  }),

  actions: {
    async fetchBrands() {
      const { $api } = useNuxtApp()

      this.brands.loading = true
      this.brands.error = null

      try {
        const { data } = await $api.brand.list()
        this.brands.data = sortBy(data, 'brand_name')
        return this.brands.data
      } catch (error) {
        this.brands.error = error
        throw error
      } finally {
        this.brands.loading = false
      }
    },

    async createBrand(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.brand.create(payload)
      this.brands.data = sortBy([...this.brands.data, data], 'brand_name')
      return data
    },

    async updateBrand(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.brand.update(id, payload)
      this.brands.data = sortBy(replaceById(this.brands.data, id, data), 'brand_name')
      return data
    },

    async deleteBrand(id) {
      const { $api } = useNuxtApp()
      await $api.brand.del(id)
      this.brands.data = this.brands.data.filter((item) => item.id !== id)
    },

    async fetchSkills() {
      const { $api } = useNuxtApp()

      this.skills.loading = true
      this.skills.error = null

      try {
        const { data } = await $api.skill.list()
        this.skills.data = sortBy(data, 'skill_name')
        return this.skills.data
      } catch (error) {
        this.skills.error = error
        throw error
      } finally {
        this.skills.loading = false
      }
    },

    async createSkill(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.skill.create(payload)
      this.skills.data = sortBy([...this.skills.data, data], 'skill_name')
      return data
    },

    async updateSkill(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.skill.update(id, payload)
      this.skills.data = sortBy(replaceById(this.skills.data, id, data), 'skill_name')
      return data
    },

    async deleteSkill(id) {
      const { $api } = useNuxtApp()
      await $api.skill.del(id)
      this.skills.data = this.skills.data.filter((item) => item.id !== id)
    },

    async fetchGroupTags() {
      const { $api } = useNuxtApp()

      this.groupTags.loading = true
      this.groupTags.error = null

      try {
        const { data } = await $api.groupTag.list()
        this.groupTags.data = sortBy(data, 'name')
        return this.groupTags.data
      } catch (error) {
        this.groupTags.error = error
        throw error
      } finally {
        this.groupTags.loading = false
      }
    },

    async createGroupTag(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.groupTag.create(payload)
      this.groupTags.data = sortBy([...this.groupTags.data, data], 'name')
      return data
    },

    async updateGroupTag(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.groupTag.update(id, payload)
      this.groupTags.data = sortBy(replaceById(this.groupTags.data, id, data), 'name')
      return data
    },

    async deleteGroupTag(id) {
      const { $api } = useNuxtApp()
      await $api.groupTag.del(id)
      this.groupTags.data = this.groupTags.data.filter((item) => item.id !== id)
    },

    async fetchCategories() {
      const { $api } = useNuxtApp()

      this.categories.loading = true
      this.categories.error = null

      try {
        const { data } = await $api.category.list()
        this.categories.data = sortBy(data, 'name')
        return this.categories.data
      } catch (error) {
        this.categories.error = error
        throw error
      } finally {
        this.categories.loading = false
      }
    },

    async updateCategory(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.category.update(id, payload)
      this.categories.data = sortBy(replaceById(this.categories.data, id, data), 'name')
      return data
    },

    async fetchClusters() {
      const { $api } = useNuxtApp()

      this.clusters.loading = true
      this.clusters.error = null

      try {
        const { data } = await $api.category.clusters()
        this.clusters.data = data
        return data
      } catch (error) {
        this.clusters.error = error
        throw error
      } finally {
        this.clusters.loading = false
      }
    },

    async fetchRoles() {
      const { $api } = useNuxtApp()

      this.roles.loading = true
      this.roles.error = null

      try {
        const { data } = await $api.role.list()
        this.roles.data = data
        return this.roles.data
      } catch (error) {
        this.roles.error = error
        throw error
      } finally {
        this.roles.loading = false
      }
    },

    async fetchPermissions() {
      const { $api } = useNuxtApp()

      this.permissions.loading = true
      this.permissions.error = null

      try {
        const { data } = await $api.role.listPermissions()
        this.permissions.data = data
        return data
      } catch (error) {
        this.permissions.error = error
        throw error
      } finally {
        this.permissions.loading = false
      }
    },

    async updateRolePermissions(id, permissions) {
      const { $api } = useNuxtApp()
      const { data } = await $api.role.updatePermissions(id, permissions)
      this.roles.data = replaceById(this.roles.data, id, data)
      return data
    },
  },
})

function sortBy(items, key) {
  return [...items].sort((a, b) => String(a[key]).localeCompare(String(b[key])))
}

function replaceById(items, id, replacement) {
  return items.map((item) => (item.id === id ? replacement : item))
}
