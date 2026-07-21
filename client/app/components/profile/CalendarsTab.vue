<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import { useSessionStore } from '~/stores/session.js'

// GET /api/v2/users/me/calendars. Functional spec:
// resources/js/components/CalendarsTab.vue +
// resources/views/user/profile/calendars.blade.php. Read-only (no save
// action) - always operates on Auth::user(), see stores/profile.js's class
// doc comment for why this tab is only ever shown while editing one's own
// profile.
const { t } = useI18n()
const profileStore = useProfileStore()
const sessionStore = useSessionStore()

const loading = ref(true)
const selectedArea = ref('')

// The legacy find-out-more link deep-links into Discourse SSO with a
// return_path back to the calendar-feed help article (general.calendar_
// feed_help_url, a path under the same origin as DISCOURSE_URL). Built the
// same way from the session's discourse_url config, since there's no
// dedicated field for it in GET /api/v2/session.
const findOutMoreUrl = computed(() => {
  const base = sessionStore.config?.discourse_url || ''
  return `${base}/session/sso?return_path=${base}${t('general.calendar_feed_help_url')}`
})

const areaUrl = computed(() =>
  selectedArea.value ? `/calendar/group-area/${encodeURIComponent(selectedArea.value)}` : '',
)

onMounted(async () => {
  try {
    const data = await profileStore.fetchCalendars()
    if (data.group_areas?.length) {
      selectedArea.value = data.group_areas[0]
    }
  } catch {
    // Load error is rendered by the retry state below.
  } finally {
    loading.value = false
  }
})

function copy(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text)
  }
}
</script>

<template>
  <div class="edit-panel" data-testid="calendars-tab">
    <h3>{{ t('profile.calendars.title') }}</h3>
    <p>
      {{ t('profile.calendars.explainer') }}
      <a :href="findOutMoreUrl">{{ t('profile.calendars.find_out_more') }}</a>.
    </p>

    <BAlert v-if="profileStore.calendars.error" :model-value="true" variant="danger" data-testid="calendars-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <div v-else-if="loading" class="text-center my-3" data-testid="calendars-loading">
      <BSpinner small />
    </div>

    <template v-else-if="profileStore.calendars.data">
      <h5>{{ t('profile.calendars.my_events') }}</h5>
      <div class="input-group mb-4">
        <input type="text" class="form-control" readonly :value="profileStore.calendars.data.user_url" data-testid="calendars-user-url">
        <BButton variant="primary" @click="copy(profileStore.calendars.data.user_url)">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>

      <h5>{{ t('profile.calendars.group_calendars') }}</h5>
      <template v-for="g in profileStore.calendars.data.groups" :key="g.id">
        <p class="mb-2">{{ g.name }}</p>
        <div class="input-group mb-4">
          <input type="text" class="form-control" readonly :value="g.url">
          <BButton variant="primary" @click="copy(g.url)">{{ t('profile.calendars.copy_link') }}</BButton>
        </div>
      </template>

      <template v-if="profileStore.calendars.data.is_admin && profileStore.calendars.data.admin_all_events_url">
        <!-- Ported verbatim from
             resources/views/partials/svg-icons/admin-cog-icon.blade.php,
             included before this admin-only heading's text in develop's
             calendars.blade.php. -->
        <h5 class="mb-3">
          <svg width="18" height="18" viewBox="0 0 50 50" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" class="me-1" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;">
            <g transform="matrix(3.17512,0,0,3.17512,-578.372,-70.5275)">
              <g transform="matrix(1,0,0,1,-1.664,0.023296)">
                <path d="M191.695,23.848C195.136,23.848 197.929,26.641 197.929,30.082C197.929,33.523 195.136,36.317 191.695,36.317C188.254,36.317 185.46,33.523 185.46,30.082C185.46,26.641 188.254,23.848 191.695,23.848ZM191.695,27.04C193.374,27.04 194.737,28.403 194.737,30.082C194.737,31.761 193.374,33.124 191.695,33.125C190.016,33.125 188.653,31.762 188.653,30.082C188.653,28.403 190.016,27.04 191.695,27.04Z" style="fill:rgb(3,148,166);" />
              </g>
              <g>
                <g transform="matrix(1.53296,0,0,1.21267,-101.264,-5.00006)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
                <g transform="matrix(-1.53296,-1.87733e-16,1.48509e-16,-1.21267,481.326,65.1725)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
              </g>
              <g transform="matrix(0.707107,-0.707107,0.707107,0.707107,34.3846,143.184)">
                <g transform="matrix(1.53296,0,0,1.21267,-101.264,-5.00006)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
                <g transform="matrix(-1.53296,-1.87733e-16,1.48509e-16,-1.21267,481.326,65.1725)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
              </g>
              <g transform="matrix(0,-1,1,0,159.945,220.117)">
                <g transform="matrix(1.53296,0,0,1.21267,-101.264,-5.00006)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
                <g transform="matrix(-1.53296,-1.87733e-16,1.48509e-16,-1.21267,481.326,65.1725)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
              </g>
              <g transform="matrix(-0.707107,-0.707107,0.707107,-0.707107,303.129,185.733)">
                <g transform="matrix(1.53296,0,0,1.21267,-101.264,-5.00006)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
                <g transform="matrix(-1.53296,-1.87733e-16,1.48509e-16,-1.21267,481.326,65.1725)">
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,1.91445,1.30941)">
                    <circle cx="188.811" cy="21.318" r="0.188" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1,0,0,1,0.506763,1.30941)">
                    <rect x="188.811" y="21.131" width="1.408" height="1.207" style="fill:rgb(3,148,166);" />
                  </g>
                  <g transform="matrix(1.26648,0,0,1.12035,-49.996,-1.04617)">
                    <path d="M190.219,21.131L188.811,21.131L188.663,22.338L190.367,22.338L190.219,21.131Z" style="fill:rgb(3,148,166);" />
                  </g>
                </g>
              </g>
            </g>
          </svg>{{ t('profile.calendars.all_events') }}
        </h5>
        <div class="input-group mb-4">
          <input type="text" class="form-control" readonly :value="profileStore.calendars.data.admin_all_events_url">
          <BButton variant="primary" @click="copy(profileStore.calendars.data.admin_all_events_url)">{{ t('profile.calendars.copy_link') }}</BButton>
        </div>
      </template>

      <h5>{{ t('profile.calendars.events_by_area') }}</h5>
      <div class="input-group mb-3">
        <label class="visually-hidden" for="calendars-area-select">{{ t('profile.calendars.events_by_area') }}</label>
        <select id="calendars-area-select" v-model="selectedArea" class="form-control" data-testid="calendars-area-select">
          <option v-for="area in profileStore.calendars.data.group_areas" :key="area" :value="area">{{ area }}</option>
        </select>
        <label v-if="selectedArea" class="visually-hidden" for="calendars-area-url">{{ t('profile.calendars.events_by_area') }}</label>
        <input v-if="selectedArea" id="calendars-area-url" type="text" class="form-control" readonly :value="areaUrl">
        <BButton variant="primary" :disabled="!selectedArea" @click="copy(areaUrl)">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>
    </template>
  </div>
</template>
