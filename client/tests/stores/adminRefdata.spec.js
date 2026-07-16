import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'

describe('stores/adminRefdata', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      brand: { list: vi.fn(), create: vi.fn(), update: vi.fn(), del: vi.fn() },
      skill: { list: vi.fn(), create: vi.fn(), update: vi.fn(), del: vi.fn() },
      groupTag: { list: vi.fn(), create: vi.fn(), update: vi.fn(), del: vi.fn() },
      category: { list: vi.fn(), update: vi.fn(), clusters: vi.fn() },
      role: { list: vi.fn(), listPermissions: vi.fn(), updatePermissions: vi.fn() },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('brands', () => {
    it('fetchBrands sorts the list by brand_name', async () => {
      mockApi.brand.list.mockResolvedValue({ data: [{ id: 2, brand_name: 'Sony' }, { id: 1, brand_name: 'Bosch' }] })

      const store = useAdminRefdataStore()
      await store.fetchBrands()

      expect(store.brands.data.map((b) => b.brand_name)).toEqual(['Bosch', 'Sony'])
      expect(store.brands.loading).toBe(false)
      expect(store.brands.error).toBeNull()
    })

    it('fetchBrands sets error and rethrows on failure', async () => {
      const error = { status: 500 }
      mockApi.brand.list.mockRejectedValue(error)

      const store = useAdminRefdataStore()
      await expect(store.fetchBrands()).rejects.toStrictEqual(error)
      expect(store.brands.error).toStrictEqual(error)
      expect(store.brands.loading).toBe(false)
    })

    it('createBrand appends the new brand and keeps the list sorted', async () => {
      const store = useAdminRefdataStore()
      store.brands.data = [{ id: 1, brand_name: 'Bosch' }]
      mockApi.brand.create.mockResolvedValue({ data: { id: 2, brand_name: 'Aiwa' } })

      const created = await store.createBrand({ brand_name: 'Aiwa' })

      expect(mockApi.brand.create).toHaveBeenCalledWith({ brand_name: 'Aiwa' })
      expect(created).toEqual({ id: 2, brand_name: 'Aiwa' })
      expect(store.brands.data.map((b) => b.brand_name)).toEqual(['Aiwa', 'Bosch'])
    })

    it('updateBrand replaces the matching row by id and re-sorts', async () => {
      const store = useAdminRefdataStore()
      store.brands.data = [{ id: 1, brand_name: 'Bosch' }, { id: 2, brand_name: 'Sony' }]
      mockApi.brand.update.mockResolvedValue({ data: { id: 1, brand_name: 'Zanussi' } })

      await store.updateBrand(1, { brand_name: 'Zanussi' })

      expect(mockApi.brand.update).toHaveBeenCalledWith(1, { brand_name: 'Zanussi' })
      expect(store.brands.data.map((b) => b.brand_name)).toEqual(['Sony', 'Zanussi'])
    })

    it('deleteBrand removes the row by id', async () => {
      const store = useAdminRefdataStore()
      store.brands.data = [{ id: 1, brand_name: 'Bosch' }, { id: 2, brand_name: 'Sony' }]
      mockApi.brand.del.mockResolvedValue(undefined)

      await store.deleteBrand(1)

      expect(mockApi.brand.del).toHaveBeenCalledWith(1)
      expect(store.brands.data).toEqual([{ id: 2, brand_name: 'Sony' }])
    })
  })

  describe('skills', () => {
    it('fetchSkills sorts by skill_name', async () => {
      mockApi.skill.list.mockResolvedValue({ data: [{ id: 2, skill_name: 'Soldering' }, { id: 1, skill_name: 'First aid' }] })

      const store = useAdminRefdataStore()
      await store.fetchSkills()

      expect(store.skills.data.map((s) => s.skill_name)).toEqual(['First aid', 'Soldering'])
    })

    it('createSkill/updateSkill/deleteSkill round-trip through $api.skill', async () => {
      const store = useAdminRefdataStore()
      mockApi.skill.create.mockResolvedValue({ data: { id: 1, skill_name: 'Soldering', category: 2 } })
      await store.createSkill({ skill_name: 'Soldering', category: 2 })
      expect(store.skills.data).toEqual([{ id: 1, skill_name: 'Soldering', category: 2 }])

      mockApi.skill.update.mockResolvedValue({ data: { id: 1, skill_name: 'Advanced soldering', category: 2 } })
      await store.updateSkill(1, { skill_name: 'Advanced soldering', category: 2 })
      expect(store.skills.data[0].skill_name).toBe('Advanced soldering')

      mockApi.skill.del.mockResolvedValue(undefined)
      await store.deleteSkill(1)
      expect(store.skills.data).toEqual([])
    })
  })

  describe('groupTags', () => {
    it('createGroupTag/updateGroupTag/deleteGroupTag round-trip through $api.groupTag', async () => {
      const store = useAdminRefdataStore()
      mockApi.groupTag.create.mockResolvedValue({ data: { id: 1, name: 'Scotland', groups_count: 0 } })
      await store.createGroupTag({ name: 'Scotland' })
      expect(store.groupTags.data).toEqual([{ id: 1, name: 'Scotland', groups_count: 0 }])

      mockApi.groupTag.update.mockResolvedValue({ data: { id: 1, name: 'Wales', groups_count: 0 } })
      await store.updateGroupTag(1, { name: 'Wales' })
      expect(store.groupTags.data[0].name).toBe('Wales')

      mockApi.groupTag.del.mockResolvedValue(undefined)
      await store.deleteGroupTag(1)
      expect(store.groupTags.data).toEqual([])
    })
  })

  describe('categories', () => {
    it('fetchCategories sorts by name; no create/delete actions exist on the store', async () => {
      mockApi.category.list.mockResolvedValue({ data: [{ id: 2, name: 'TV' }, { id: 1, name: 'Laptop' }] })

      const store = useAdminRefdataStore()
      await store.fetchCategories()

      expect(store.categories.data.map((c) => c.name)).toEqual(['Laptop', 'TV'])
      expect(store.createCategory).toBeUndefined()
      expect(store.deleteCategory).toBeUndefined()
    })

    it('updateCategory replaces the matching row', async () => {
      const store = useAdminRefdataStore()
      store.categories.data = [{ id: 1, name: 'Laptop', cluster_name: null }]
      mockApi.category.update.mockResolvedValue({ data: { id: 1, name: 'Laptop', cluster_name: 'Computers' } })

      await store.updateCategory(1, { name: 'Laptop', cluster: 3 })

      expect(mockApi.category.update).toHaveBeenCalledWith(1, { name: 'Laptop', cluster: 3 })
      expect(store.categories.data[0].cluster_name).toBe('Computers')
    })

    it('fetchClusters populates clusters.data', async () => {
      mockApi.category.clusters.mockResolvedValue({ data: [{ id: 1, name: 'Computers and Home Office' }] })

      const store = useAdminRefdataStore()
      await store.fetchClusters()

      expect(store.clusters.data).toEqual([{ id: 1, name: 'Computers and Home Office' }])
    })
  })

  describe('roles', () => {
    it('fetchRoles and fetchPermissions populate their sections independently', async () => {
      mockApi.role.list.mockResolvedValue({ data: [{ id: 1, name: 'Host', permissions: [4], permissions_list: 'Create Party' }] })
      mockApi.role.listPermissions.mockResolvedValue({ data: [{ id: 4, name: 'Create Party' }] })

      const store = useAdminRefdataStore()
      await Promise.all([store.fetchRoles(), store.fetchPermissions()])

      expect(store.roles.data).toEqual([{ id: 1, name: 'Host', permissions: [4], permissions_list: 'Create Party' }])
      expect(store.permissions.data).toEqual([{ id: 4, name: 'Create Party' }])
    })

    it('updateRolePermissions replaces the matching role row with the server response', async () => {
      const store = useAdminRefdataStore()
      store.roles.data = [{ id: 1, name: 'Host', permissions: [], permissions_list: '' }]
      mockApi.role.updatePermissions.mockResolvedValue({ data: { id: 1, name: 'Host', permissions: [4, 6], permissions_list: 'Create Party, View Reports' } })

      await store.updateRolePermissions(1, [4, 6])

      expect(mockApi.role.updatePermissions).toHaveBeenCalledWith(1, [4, 6])
      expect(store.roles.data[0].permissions).toEqual([4, 6])
    })
  })
})
