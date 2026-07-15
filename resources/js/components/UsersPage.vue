<template>
  <section>
    <div class="container">
      <div class="row mb-30">
        <div class="col-12">
          <div class="d-flex align-items-center">
            <h1 class="mb-0 mr-30">{{ __('users.title') }}</h1>
            <div class="button-group-filters ml-auto">
              <b-btn
                v-b-toggle.collapseFilter
                variant="secondary"
                class="d-md-none d-lg-none d-xl-none"
              >
                {{ __('users.reveal_filters') }}
              </b-btn>
              <b-btn variant="primary" href="#" data-toggle="modal" data-target="#add">
                {{ __('users.create_new') }}
              </b-btn>
            </div>
          </div>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-md-4 col-lg-3">
          <b-collapse id="collapseFilter" visible class="d-md-block d-lg-block d-xl-block">
            <aside class="panel p-3">
              <b-form @submit.prevent="applyFilters">
                <b-form-group :label="__('users.name')" label-for="filter-name">
                  <b-form-input
                      id="filter-name"
                      v-model="filters.name"
                      :placeholder="__('users.placeholder_name')"
                      data-testid="users-filter-name"
                  />
                </b-form-group>

                <b-form-group :label="__('users.email')" label-for="filter-email">
                  <b-form-input
                      id="filter-email"
                      v-model="filters.email"
                      :placeholder="__('users.placeholder_email')"
                      data-testid="users-filter-email"
                  />
                </b-form-group>

                <b-form-group :label="__('users.location')" label-for="filter-location">
                  <b-form-input
                      id="filter-location"
                      v-model="filters.location"
                      :placeholder="__('users.placeholder_location')"
                      data-testid="users-filter-location"
                  />
                </b-form-group>

                <b-form-group :label="__('users.country')" label-for="filter-country">
                  <b-form-select
                      id="filter-country"
                      v-model="filters.country"
                      :options="countryOptions"
                      data-testid="users-filter-country"
                  />
                </b-form-group>

                <b-form-group :label="__('users.role')" label-for="filter-role">
                  <b-form-select
                      id="filter-role"
                      v-model="filters.role"
                      :options="roleOptions"
                      data-testid="users-filter-role"
                  />
                </b-form-group>

                <b-btn type="submit" variant="secondary" block data-testid="users-filter-submit">
                  {{ __('users.search') }}
                </b-btn>
              </b-form>
            </aside>
          </b-collapse>
        </div>

        <div class="col-md-8 col-lg-9">
          <div class="table-responsive panel">
            <b-table
                :items="users"
                :fields="tableFields"
                :busy="loading"
                striped
                hover
                sort-icon-left
                :sort-by.sync="sortBy"
                :sort-desc.sync="sortDesc"
                :no-local-sorting="true"
                @sort-changed="onSortChanged"
                show-empty
                :empty-text="__('users.empty')"
                data-testid="users-table"
            >
              <template #cell(name)="row">
                <a v-if="canEdit" :href="`/user/edit/${row.item.id}`">{{ row.item.name }}</a>
                <span v-else>{{ row.item.name }}</span>
              </template>
              <template #cell(role_name)="row">{{ row.item.role_name }}</template>
              <template #cell(created_at)="row">
                <span :title="row.item.created_at">{{ humanise(row.item.created_at) }}</span>
              </template>
              <template #cell(last_login_at)="row">
                <span :title="row.item.last_login_at">{{ humanise(row.item.last_login_at) }}</span>
              </template>
            </b-table>

            <div class="d-flex justify-content-center">
              <b-pagination
                  v-if="meta.total > meta.per_page"
                  v-model="page"
                  :total-rows="meta.total"
                  :per-page="meta.per_page"
                  align="center"
                  data-testid="users-pagination"
                  @change="onPageChange"
              />
            </div>

            <div v-if="meta.total > 0" class="d-flex justify-content-center">
              {{ __('users.showing', { from: meta.from, to: meta.to, total: meta.total }) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    countries: {
      type: Array,
      required: true,
    },
    roles: {
      type: Array,
      required: true,
    },
    canEdit: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      filters: {
        name: '',
        email: '',
        location: '',
        country: '',
        role: '',
      },
      page: 1,
      sortBy: '',
      sortDesc: false,
      users: [],
      meta: { current_page: 1, total: 0, per_page: 30, from: 0, to: 0 },
      loading: false,
    }
  },
  computed: {
    countryOptions() {
      return [{ value: '', text: '' }].concat(
          this.countries.map(c => ({ value: c.code, text: c.name }))
      )
    },
    roleOptions() {
      return [{ value: '', text: this.__('users.role_any') }].concat(
          this.roles.map(r => ({ value: r.id, text: r.name }))
      )
    },
    tableFields() {
      return [
        { key: 'name', label: this.__('users.name'), sortable: true },
        { key: 'email', label: this.__('users.email'), sortable: true, class: 'd-none d-sm-table-cell' },
        { key: 'role_name', label: this.__('users.role'), sortable: true },
        { key: 'location', label: this.__('users.location'), sortable: true, class: 'd-none d-sm-table-cell' },
        { key: 'country_name', label: this.__('users.country'), sortable: true, sortKey: 'country', class: 'd-none d-sm-table-cell' },
        { key: 'groups_count', label: this.__('users.groups'), class: 'd-none d-sm-table-cell' },
        { key: 'created_at', label: this.__('users.joined'), sortable: true, class: 'd-none d-sm-table-cell' },
        { key: 'last_login_at', label: this.__('users.last_login'), sortable: true, sortKey: 'updated_at', class: 'd-none d-sm-table-cell' },
      ]
    },
  },
  mounted() {
    this.fetch()
  },
  methods: {
    async fetch() {
      this.loading = true
      try {
        const params = { page: this.page }
        if (this.filters.name) params.name = this.filters.name
        if (this.filters.email) params.email = this.filters.email
        if (this.filters.location) params.location = this.filters.location
        if (this.filters.country) params.country = this.filters.country
        if (this.filters.role) params.role = this.filters.role
        if (this.sortBy) {
          const field = this.tableFields.find(f => f.key === this.sortBy)
          params.sort = (field && field.sortKey) || this.sortBy
          params.sortdir = this.sortDesc ? 'desc' : 'asc'
        }
        const { data } = await axios.get('/api/v2/users', { params })
        this.users = data.data
        this.meta = data.meta
      } catch (e) {
        console.error('Failed to load users', e)
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.page = 1
      this.fetch()
    },
    onPageChange(newPage) {
      this.page = newPage
      this.fetch()
    },
    onSortChanged(ctx) {
      this.sortBy = ctx.sortBy
      this.sortDesc = ctx.sortDesc
      this.page = 1
      this.fetch()
    },
    humanise(iso) {
      if (!iso) return this.__('users.never')
      try {
        const d = new Date(iso)
        return d.toLocaleDateString()
      } catch (e) {
        return iso
      }
    },
  },
}
</script>
