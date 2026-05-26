<template>
  <div>
    <div class="network-header d-flex align-items-center mb-4">
      <div v-if="network.logo" class="network-logo mr-4">
        <img :src="'/uploads/' + network.logo" :alt="network.name + ' logo'" class="img-fluid" style="max-height: 60px;">
      </div>
      <div class="flex-grow-1">
        <h1>{{ network.name }}</h1>
        <a v-if="network.website" :href="network.website" target="_blank" rel="noopener noreferrer" class="text-muted">
          {{ network.website }}
        </a>
      </div>
      <div class="network-actions" v-if="isLoggedIn">
        <b-dropdown right variant="primary" :text="__('networks.general.actions')">
          <b-dropdown-item :href="'/group/network/' + network.id">{{ __('networks.show.view_groups_menuitem') }}</b-dropdown-item>
          <b-dropdown-item v-if="canAssociateGroups" @click="showAddGroupModal">{{ __('networks.show.add_groups_menuitem') }}</b-dropdown-item>
          <b-dropdown-item :href="'/export/networks/' + network.id + '/events'">{{ __('groups.export_event_list') }}</b-dropdown-item>
        </b-dropdown>
      </div>
    </div>

    <!-- Impact Stats -->
    <div class="network-stats mb-4">
      <h2>{{ __('networks.general.impact') }}</h2>
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-value">{{ stats.groups || 0 }}</div>
          <div class="stat-label">{{ __('networks.stats.groups', { count: stats.groups || 0 }) }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ stats.parties || 0 }}</div>
          <div class="stat-label">{{ __('networks.stats.events', { count: stats.parties || 0 }) }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ formatWeight(stats.waste_total) }}</div>
          <div class="stat-label">{{ __('networks.stats.waste_diverted') }}</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ formatWeight(stats.co2_total) }}</div>
          <div class="stat-label">{{ __('networks.stats.co2_prevented') }}</div>
        </div>
      </div>
    </div>

    <!-- About (moved from column layout) -->
    <section class="mb-4" v-if="network.description">
      <h2>{{ __('networks.general.about') }}</h2>
      <div class="network-description" v-html="truncatedDescription"></div>
      <button v-if="showReadMore" class="btn btn-link p-0" @click="showDescriptionModal = true">
        {{ __('partials.read_more') }}
      </button>
    </section>

    <!-- Network Coordinators (horizontal) -->
    <section class="mb-4" v-if="network.coordinators && network.coordinators.length">
      <h2>{{ __('networks.general.coordinators') }}</h2>
      <div class="coordinators-horizontal">
        <a v-for="coordinator in network.coordinators" :key="coordinator.id" :href="'/profile/' + coordinator.id" class="coordinator-card">
          <img :src="coordinator.picture" :alt="coordinator.name" class="coordinator-avatar">
          <span class="coordinator-name">{{ coordinator.name }}</span>
        </a>
      </div>
    </section>

    <!-- Groups requiring moderation (full width) -->
    <section class="mb-4">
      <h2>{{ __('groups.groups_title_admin') }}</h2>
      <GroupsRequiringModeration :networks="[network.id]" ref="groupsModeration" />
      <div v-if="groupsModerationEmpty" class="text-muted">{{ __('networks.show.none') }}</div>
    </section>

    <!-- Events requiring moderation (full width) -->
    <section class="mb-4">
      <h2>{{ __('events.events_title_admin') }}</h2>
      <EventsRequiringModeration :networks="[network.id]" ref="eventsModeration" />
      <div v-if="eventsModerationEmpty" class="text-muted">{{ __('networks.show.none') }}</div>
    </section>

    <!-- Groups Map -->
    <section class="groups-section mb-4">
      <h2>{{ __('networks.general.groups') }}</h2>
      <GroupMapAndList
          :network="network.id"
          :initial-bounds="mapBounds"
          :show-filters="true"
          :can-manage-tags="canManageTags"
          :available-tags="tags"
          fetch-groups
      />
    </section>

    <div class="row">
      <div class="col-lg-6">
        <!-- Tag Management (for NCs and Admins) -->
        <section class="mb-4" v-if="canManageTags">
          <h2>{{ __('networks.tags.title') }}</h2>
          <div class="tags-management">
            <div v-if="tags.length === 0" class="text-muted mb-2">
              {{ __('networks.tags.no_tags') }}
            </div>
            <div :key="'tags-list-' + tags.length" class="tags-list mb-3" v-show="tags.length > 0">
              <div v-for="tag in tags" :key="tag.id" class="tag-item mb-2 p-2 border rounded">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <strong>{{ tag.name }}</strong>
                    <span class="text-muted ml-2">({{ tag.groups_count }} {{ tag.groups_count === 1 ? 'group' : 'groups' }})</span>
                  </div>
                  <div class="tag-actions">
                    <b-button variant="link" size="sm" class="edit-tag-btn p-0 mr-2" @click="openEditTag(tag)">
                      <span class="sr-only">{{ __('networks.tags.edit') }}</span>
                      <img :src="imageUrl('/images/pencil.svg')" alt="" class="edit-icon">
                    </b-button>
                    <b-button variant="link" size="sm" class="delete-tag-btn p-0" @click="confirmDeleteTag(tag)">
                      <span class="sr-only">{{ __('networks.tags.delete') }}</span>
                      <img :src="imageUrl('/images/trash.svg')" alt="" class="delete-icon">
                    </b-button>
                  </div>
                </div>
                <div v-if="tag.description" class="tag-description text-muted small mt-1" v-html="tag.description"></div>
              </div>
            </div>
            <div class="create-tag">
              <b-form @submit.prevent="createTag">
                <b-form-input
                    v-model="newTagName"
                    :placeholder="__('networks.tags.new_tag_placeholder')"
                    size="sm"
                    required
                    class="tag-name-input mb-2"
                />
                <b-form-input
                    v-model="newTagDescription"
                    :placeholder="__('networks.tags.description_placeholder')"
                    size="sm"
                    class="tag-description-input mb-2"
                />
                <b-button type="submit" variant="primary" size="sm" :disabled="!newTagName.trim()">
                  {{ __('networks.tags.create') }}
                </b-button>
              </b-form>
              <div v-if="tagError" class="text-danger small mt-1">{{ tagError }}</div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Delete Tag Confirmation Modal -->
    <b-modal v-model="showDeleteModal" :title="__('networks.tags.delete_confirm_title')" @ok="deleteTag">
      <p>{{ __('networks.tags.delete_confirm_message', { name: tagToDelete ? tagToDelete.name : '' }) }}</p>
      <p v-if="tagToDelete && tagToDelete.groups_count > 0" class="text-warning">
        <strong>{{ __('networks.tags.delete_warning', { count: tagToDelete.groups_count }) }}</strong>
      </p>
    </b-modal>

    <!-- Edit Tag Modal -->
    <b-modal v-model="showEditModal" :title="__('networks.tags.edit_title')" @ok.prevent="updateTag" :ok-disabled="!editTagName.trim()">
      <b-form-group :label="__('networks.tags.name_label')">
        <b-form-input v-model="editTagName" required />
      </b-form-group>
      <b-form-group :label="__('networks.tags.description_label')">
        <b-form-textarea v-model="editTagDescription" rows="3" />
      </b-form-group>
      <div v-if="editTagError" class="text-danger small">{{ editTagError }}</div>
    </b-modal>

    <!-- Description Modal -->
    <b-modal v-model="showDescriptionModal" :title="network.name" size="lg" ok-only>
      <div v-html="network.description"></div>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import GroupsRequiringModeration from './GroupsRequiringModeration.vue'
import EventsRequiringModeration from './EventsRequiringModeration.vue'
import GroupMapAndList from './GroupMapAndList.vue'
import images from '../mixins/images'

export default {
  components: { GroupsRequiringModeration, EventsRequiringModeration, GroupMapAndList },
  mixins: [images],
  props: {
    network: {
      type: Object,
      required: true
    },
    initialStats: {
      type: Object,
      required: false,
      default: () => ({})
    },
    initialTags: {
      type: Array,
      required: false,
      default: () => []
    },
    canManageTags: {
      type: Boolean,
      required: false,
      default: false
    },
    canAssociateGroups: {
      type: Boolean,
      required: false,
      default: false
    },
    isLoggedIn: {
      type: Boolean,
      required: false,
      default: false
    },
    apiToken: {
      type: String,
      required: false,
      default: null
    },
    mapBounds: {
      type: Array,
      required: false,
      default: () => []
    }
  },
  data() {
    return {
      stats: this.initialStats,
      // Copy the array so we don't accidentally mutate the prop or share
      // its reactive observer with the parent.
      tags: Array.isArray(this.initialTags) ? [...this.initialTags] : [],
      newTagName: '',
      newTagDescription: '',
      tagError: null,
      showDeleteModal: false,
      tagToDelete: null,
      showEditModal: false,
      editingTag: null,
      editTagName: '',
      editTagDescription: '',
      editTagError: null,
      showDescriptionModal: false
    }
  },
  computed: {
    truncatedDescription() {
      if (!this.network.description) return ''
      const stripped = this.network.description.replace(/<[^>]*>/g, '')
      if (stripped.length <= 160) return this.network.description
      return stripped.substring(0, 160) + '...'
    },
    showReadMore() {
      if (!this.network.description) return false
      const stripped = this.network.description.replace(/<[^>]*>/g, '')
      return stripped.length > 160
    },
    groupsModerationEmpty() {
      const allGroups = Object.values(this.$store.getters['groups/getModerate'] || {})
      const networkGroups = allGroups.filter(g =>
        g.networks && g.networks.some(n => n.id === this.network.id)
      )
      return networkGroups.length === 0
    },
    eventsModerationEmpty() {
      const allEvents = Object.values(this.$store.getters['events/getModerate'] || {})
      const networkEvents = allEvents.filter(e =>
        e.group && e.group.networks && e.group.networks.some(n => n.id === this.network.id)
      )
      return networkEvents.length === 0
    }
  },
  methods: {
    formatWeight(value) {
      if (!value) return '0 kg'
      if (value >= 1000) {
        return (value / 1000).toFixed(1) + ' t'
      }
      return Math.round(value) + ' kg'
    },
    showAddGroupModal() {
      // Trigger the existing add group modal via jQuery (legacy integration)
      window.$('#network-add-group').modal('show')
    },
    async createTag() {
      if (!this.newTagName.trim()) return

      this.tagError = null

      try {
        const response = await axios.post(`/api/v2/networks/${this.network.id}/tags?api_token=${this.apiToken}`, {
          name: this.newTagName.trim(),
          description: this.newTagDescription.trim() || null
        })

        console.log('[NetPage.createTag] before splice, tags.length:', this.tags.length, '_uid:', this._uid)
        // Vue 2 reliably intercepts array splice (it's in the patched method list);
        // reassignment via spread had been missing the render in some lifecycle states.
        this.tags.splice(this.tags.length, 0, response.data.data)
        console.log('[NetPage.createTag] after splice, tags.length:', this.tags.length)
        this.newTagName = ''
        this.newTagDescription = ''
        await this.$nextTick()
        const dom = this.$el && this.$el.querySelector ? this.$el.querySelector('.tags-management') : null
        const domSnippet = dom ? dom.outerHTML.substring(0, 200) : 'no .tags-management in $el'
        console.log('[NetPage.createTag] after nextTick, _isMounted:', this._isMounted, '_isDestroyed:', this._isDestroyed, 'mountedDom:', domSnippet)
      } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
          this.tagError = error.response.data.message
        } else {
          this.tagError = this.__('networks.tags.create_error')
        }
      }
    },
    confirmDeleteTag(tag) {
      this.tagToDelete = tag
      this.showDeleteModal = true
    },
    async deleteTag() {
      if (!this.tagToDelete) return

      try {
        await axios.delete(`/api/v2/networks/${this.network.id}/tags/${this.tagToDelete.id}?api_token=${this.apiToken}`)
        this.tags = this.tags.filter(t => t.id !== this.tagToDelete.id)
        this.tagToDelete = null
      } catch (error) {
        console.error('Failed to delete tag:', error)
      }
    },
    openEditTag(tag) {
      this.editingTag = tag
      this.editTagName = tag.name
      this.editTagDescription = tag.description || ''
      this.editTagError = null
      this.showEditModal = true
    },
    async updateTag(bvModalEvt) {
      if (bvModalEvt) bvModalEvt.preventDefault()
      if (!this.editingTag || !this.editTagName.trim()) return

      this.editTagError = null

      try {
        const response = await axios.put(`/api/v2/networks/${this.network.id}/tags/${this.editingTag.id}?api_token=${this.apiToken}`, {
          name: this.editTagName.trim(),
          description: this.editTagDescription.trim() || null
        })

        // Update the tag in the list
        const index = this.tags.findIndex(t => t.id === this.editingTag.id)
        if (index !== -1) {
          this.tags.splice(index, 1, response.data.data)
        }

        this.showEditModal = false
        this.editingTag = null
      } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
          this.editTagError = error.response.data.message
        } else {
          this.editTagError = this.__('networks.tags.edit_error')
        }
      }
    }
  },
  async mounted() {
    // Fetch stats if not provided
    if (!this.initialStats || Object.keys(this.initialStats).length === 0) {
      try {
        const response = await axios.get(`/api/networks/${this.network.id}/stats/?api_token=${this.apiToken}`)
        this.stats = response.data
      } catch (error) {
        console.error('Failed to fetch network stats:', error)
      }
    }

  }
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.stat-box {
  background: $white;
  border: 2px solid $black;
  padding: 1rem;
  text-align: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: $brand-light;
}

.stat-label {
  font-size: 0.875rem;
  color: $brand-placeholder;
  text-transform: uppercase;
}

.coordinators-horizontal {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;

  .coordinator-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem 0.5rem 0.5rem;
    background: $white;
    border: 2px solid $black;
    border-radius: 50px;
    text-decoration: none;
    color: inherit;
    transition: box-shadow 0.2s, border-color 0.2s;

    &:hover {
      border-color: $brand-light;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      text-decoration: none;
    }

    .coordinator-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .coordinator-name {
      font-weight: 500;
      white-space: nowrap;
    }
  }
}

.groups-section {
  .groups-info {
    background: $white;
  }
}

.tags-management {
  .tag-item {
    background: $brand-grey;
  }

  .tag-actions {
    display: flex;
    align-items: center;
  }

  .edit-tag-btn {
    .edit-icon {
      width: 18px;
      height: 18px;
    }
  }

  .delete-tag-btn {
    .delete-icon {
      width: 20px;
      height: 20px;
    }
  }

  .create-tag {
    .tag-name-input {
      flex: 1 1 auto;
      min-width: 150px;
    }

    .tag-description-input {
      width: 100%;
      margin-top: 0.5rem;
    }

    input.form-control {
      // Prevent border width change on focus causing layout shift
      border-width: 1px;
      &:focus {
        border-width: 1px;
        box-shadow: 0 0 0 2px rgba($brand-light, 0.25);
      }
    }

    button {
      flex-shrink: 0;
      white-space: nowrap;
    }
  }
}
</style>
