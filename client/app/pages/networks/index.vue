<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useSessionStore } from '~/stores/session.js'
import { useNetworksStore } from '~/stores/networks.js'

// /networks - resources/views/networks/index.blade.php +
// NetworkController@index (design.md §6.2 Phase E task E1). Administrator/
// NetworkCoordinator-only (the legacy controller aborts(403) for anyone
// else) - there is no single `role:` value that covers "either of two
// roles" (middleware/auth.global.ts only supports one), so this page does
// its own OR-gate on mount and navigates to /forbidden itself, mirroring
// the 403.
//
// "Your networks" = the networks the session user coordinates
// (GET /api/v2/session's `user.networks`, {id, name} - SessionController).
// "All networks" (Administrator only) = every network on the platform
// (GET /api/v2/networks, public). Both tables are built from the same
// NetworkSummary list so logos are available for "your networks" too.
// App\Http\Resources\NetworkSummary now carries `description` too, so the
// legacy page's Description column is restored on "Your networks" (matching
// resources/views/networks/index.blade.php, which only showed it there, not
// on "All networks") - closes the gap recorded in
// docs/nuxt-migration/api-gaps.md Phase E.
//
// Legacy always rendered a logo cell (real logo, or a generic placeholder
// image when the network has none) - ported here using the same
// placeholder-avatar asset the groups pages already fall back to for a
// missing image (client/public/images/placeholder-avatar.webp), since the
// legacy page's own generic-network-logo upload path isn't a client asset.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('networks.general.networks') })

const { hasRole } = useAuth()
const sessionStore = useSessionStore()
const networksStore = useNetworksStore()

const isAdministrator = computed(() => hasRole('Administrator'))
const isNetworkCoordinator = computed(() => hasRole('NetworkCoordinator'))
const showAllNetworks = computed(() => isAdministrator.value)

const yourNetworkIds = computed(() => new Set((sessionStore.user?.networks || []).map((n) => n.id)))

const yourNetworks = computed(() =>
  networksStore.list.data
    .filter((network) => yourNetworkIds.value.has(network.id))
    .sort((a, b) => a.name.localeCompare(b.name))
)

const allNetworks = computed(() => [...networksStore.list.data].sort((a, b) => a.name.localeCompare(b.name)))

function load() {
  networksStore.fetchList()
}

onMounted(() => {
  if (!isAdministrator.value && !isNetworkCoordinator.value) {
    navigateTo('/forbidden')
    return
  }

  load()
})

function retry() {
  load()
}
</script>

<template>
  <div class="container py-4" data-testid="networks-index-page">
    <h1>{{ t('networks.general.networks') }}</h1>

    <div v-if="networksStore.list.loading" data-testid="networks-index-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="networksStore.list.error" :model-value="true" variant="danger" data-testid="networks-index-load-error">
      <p>{{ t('client.networks.load_error') }}</p>
      <BButton variant="danger" data-testid="networks-index-retry" @click="retry">{{ t('client.dashboard.retry') }}</BButton>
    </BAlert>

    <template v-else>
      <section class="mb-4" data-testid="your-networks-section">
        <h2>{{ t('networks.index.your_networks') }}</h2>
        <p>{{ t('networks.index.your_networks_explainer') }}</p>

        <div v-if="!yourNetworks.length" class="text-muted" data-testid="your-networks-empty">
          {{ t('networks.index.your_networks_no_networks') }}
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover table-layout-fixed" data-testid="your-networks-table">
            <thead>
              <tr>
                <th scope="col" width="20%" />
                <th scope="col" width="20%">{{ t('networks.general.network') }}</th>
                <th scope="col">{{ t('networks.index.description') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="network in yourNetworks" :key="network.id" :data-testid="`your-network-row-${network.id}`">
                <td>
                  <img :src="network.logo || '/images/placeholder-avatar.webp'" :alt="t('client.networks.logo_alt', { name: network.name })" style="width: auto; height: 50px">
                </td>
                <td>
                  <NuxtLink :to="`/networks/${network.id}`" :data-testid="`your-network-link-${network.id}`">
                    {{ network.name }}
                  </NuxtLink>
                </td>
                <td :data-testid="`your-network-description-${network.id}`">{{ network.description }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showAllNetworks" data-testid="all-networks-section">
        <h2>{{ t('networks.index.all_networks') }}</h2>
        <p>{{ t('networks.index.all_networks_explainer') }}</p>

        <div v-if="!allNetworks.length" class="text-muted" data-testid="all-networks-empty">
          {{ t('networks.index.all_networks_no_networks') }}
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover table-layout-fixed" data-testid="all-networks-table">
            <thead>
              <tr>
                <th scope="col" width="20%" />
                <th scope="col" width="20%">{{ t('networks.general.network') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="network in allNetworks" :key="network.id" :data-testid="`all-network-row-${network.id}`">
                <td>
                  <img :src="network.logo || '/images/placeholder-avatar.webp'" :alt="t('client.networks.logo_alt', { name: network.name })" style="width: auto; height: 50px">
                </td>
                <td>
                  <NuxtLink :to="`/networks/${network.id}`" :data-testid="`all-network-link-${network.id}`">
                    {{ network.name }}
                  </NuxtLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>
