<script setup>
import { useI18n } from 'vue-i18n'

// Tab nav shared by /group and /group/map. PR 887 (RES-1995)'s
// GroupsPage.vue is the functional spec: exactly two tabs - "Your Groups"
// (groups_title1) and "Find a group" (groups_title2), where "Find a group"
// IS the map+list. Legacy keeps them as two panels of one <b-tabs
// class="ourtabs"> and rewrites the URL on tab change; here each is its
// own Nuxt page/route, so this is just a small nav bar highlighting the
// active one, wrapping a default slot so the page content below it shares
// the same bordered/shadowed box legacy's single .ourtabs element draws
// around both its nav *and* its tab-content (resources/sass/
// _events.scss:326-368).
//
// The port originally had pre-887 develop's three list tabs
// (mine/nearby/all) with the map on an unlinked side route - that matched
// the wrong baseline for a branch integrating 887. /group/nearby and
// /group/all now redirect to /group/map (their pages carry the history);
// what /group/all's list had beyond the map page's list panel (full-list
// filter bar, tag badges, archived groups) is 887's GroupMapAndList list
// functionality, to be ported into pages/group/map.vue's panel.
const props = defineProps({
  active: {
    type: String,
    required: true,
    validator: (value) => ['mine', 'map'].includes(value),
  },
})

const { t } = useI18n()

function isActive(tab) {
  return props.active === tab
}
</script>

<template>
  <div class="groups-tabs-panel" data-testid="groups-tabs-panel">
    <nav class="groups-tabs" data-testid="groups-tabs">
      <NuxtLink
        to="/group"
        class="groups-tab"
        :class="{ active: isActive('mine') }"
        data-testid="groups-tab-mine"
        :aria-current="isActive('mine') ? 'page' : undefined"
      >
        <b>{{ t('groups.groups_title1') }}</b>
      </NuxtLink>
      <NuxtLink
        to="/group/map"
        class="groups-tab"
        :class="{ active: isActive('map') }"
        data-testid="groups-tab-map"
        :aria-current="isActive('map') ? 'page' : undefined"
      >
        <b>{{ t('groups.groups_title2') }}</b>
      </NuxtLink>
    </nav>
    <div class="groups-tabs-panel-body">
      <slot />
    </div>
  </div>
</template>

<style scoped lang="scss">
// Reproduces legacy's ".ourtabs" chrome (resources/sass/_events.scss) around
// this plain nav plus its slotted content, rather than b-tabs: bordered/
// drop-shadowed box, uppercase justified/equal-width tabs, and a thick top
// border (no bottom border, at md+) on the active tab - see this file's own
// doc comment above for why this is a plain nav+slot (one tab per route)
// rather than legacy's b-tabs panels.
.groups-tabs-panel {
  margin-bottom: 1rem;
  border: 1px solid #000;
  box-shadow: 5px 5px #000;
  background-color: #fff;
}

.groups-tabs {
  display: flex;
}

.groups-tabs-panel-body {
  padding: 1rem;
}

.groups-tab {
  flex: 1 1 0;
  padding: 0.75rem 0.5rem;
  border-right: 1px solid #000;
  border-bottom: 1px solid #000;
  text-transform: uppercase;
  text-align: center;
  color: inherit;
  text-decoration: none;

  &:last-child {
    border-right: 0;
  }

  &.active {
    border-top: 5px solid #000;
    padding-top: calc(0.75rem - 4px);

    @media (min-width: 768px) {
      border-bottom: none;
    }
  }
}
</style>
