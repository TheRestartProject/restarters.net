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

    <!-- Groups -->
    <section class="groups-section mb-4">
      <h2>{{ __('networks.general.groups') }}</h2>
      <div class="groups-info border p-3">
        {{ __('networks.show.groups_count', { count: stats.groups || 0, name: network.name }) }}
        <a :href="'/group/network/' + network.id">{{ __('networks.show.view_groups_link') }}</a>
      </div>
    </section>

    <div class="row">
      <div class="col-lg-4">
        <!-- Coordinators -->
        <section class="mb-4" v-if="network.coordinators && network.coordinators.length">
          <h2>{{ __('networks.general.coordinators') }}</h2>
          <ul class="list-unstyled coordinators-list">
            <li v-for="coordinator in network.coordinators" :key="coordinator.id" class="coordinator-item d-flex align-items-center mb-3">
              <img :src="coordinator.picture" :alt="coordinator.name" class="coordinator-avatar rounded-circle mr-3">
              <div>
                <a :href="'/profile/' + coordinator.id" class="coordinator-name">{{ coordinator.name }}</a>
                <div><span class="badge badge-primary">{{ __('networks.general.coordinator_badge') }}</span></div>
              </div>
            </li>
          </ul>
        </section>

        <!-- Tag Management (for NCs and Admins) -->
        <section class="mb-4" v-if="canManageTags">
          <h2>{{ __('networks.tags.title') }}</h2>
          <div class="tags-management">
            <div v-if="tags.length === 0" class="text-muted mb-2">
              {{ __('networks.tags.no_tags') }}
            </div>
            <div v-else class="tags-list mb-3">
              <div v-for="tag in tags" :key="tag.id" class="tag-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div>
                  <strong>{{ tag.name }}</strong>
                  <span class="text-muted ml-2">({{ tag.groups_count }} {{ tag.groups_count === 1 ? 'group' : 'groups' }})</span>
                </div>
                <b-button variant="link" size="sm" class="delete-tag-btn p-0" @click="confirmDeleteTag(tag)">
                  <span class="sr-only">{{ __('networks.tags.delete') }}</span>
                  <img :src="imageUrl('/images/trash.svg')" alt="" class="delete-icon">
                </b-button>
              </div>
            </div>
            <div class="create-tag">
              <b-form @submit.prevent="createTag" inline>
                <b-form-input
                    v-model="newTagName"
                    :placeholder="__('networks.tags.new_tag_placeholder')"
                    class="mr-2 mb-2"
                    size="sm"
                    required
                />
                <b-button type="submit" variant="primary" size="sm" class="mb-2" :disabled="!newTagName.trim()">
                  {{ __('networks.tags.create') }}
                </b-button>
              </b-form>
              <div v-if="tagError" class="text-danger small mt-1">{{ tagError }}</div>
            </div>
          </div>
        </section>
      </div>

      <div class="col-lg-8">
        <!-- About -->
        <section class="mb-4" v-if="network.description">
          <h2>{{ __('networks.general.about') }}</h2>
          <div class="network-description" v-html="truncatedDescription"></div>
          <button v-if="showReadMore" class="btn btn-link p-0" @click="showDescriptionModal = true">
            {{ __('partials.read_more') }}
          </button>
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
import images from '../mixins/images'

export default {
  components: { GroupsRequiringModeration, EventsRequiringModeration },
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
    }
  },
  data() {
    return {
      stats: this.initialStats,
      tags: this.initialTags,
      newTagName: '',
      tagError: null,
      showDeleteModal: false,
      tagToDelete: null,
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
          name: this.newTagName.trim()
        })

        this.tags.push(response.data.data)
        this.newTagName = ''
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

    // Fetch tags if not provided and user can manage tags
    if (this.canManageTags && (!this.initialTags || this.initialTags.length === 0)) {
      try {
        const response = await axios.get(`/api/v2/networks/${this.network.id}/tags?api_token=${this.apiToken}`)
        this.tags = response.data.data || []
      } catch (error) {
        console.error('Failed to fetch network tags:', error)
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

.coordinators-list {
  .coordinator-item {
    border: 1px solid $brand-grey;
    padding: 0.75rem;
    border-radius: 4px;
  }

  .coordinator-avatar {
    width: 50px;
    height: 50px;
    object-fit: cover;
  }

  .coordinator-name {
    font-weight: bold;
    color: inherit;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
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

  .delete-tag-btn {
    .delete-icon {
      width: 20px;
      height: 20px;
    }
  }
}
</style>
