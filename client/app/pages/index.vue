<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'
import IconFixometer from '~/components/icons/IconFixometer.vue'
import IconWiki from '~/components/icons/IconWiki.vue'
import IconGroups from '~/components/icons/IconGroups.vue'
import IconTalk from '~/components/icons/IconTalk.vue'
import IconEvents from '~/components/icons/IconEvents.vue'

// Port of resources/views/landing.blade.php: logged-in visitors land here
// transiently on refresh/bookmark of "/" - send them straight to the
// dashboard (design.md §6.2 section 9). Anonymous visitors see the real
// marketing landing content, reusing the generated `landing.*` lang keys
// verbatim (Laravel-side source: lang/en/landing.php) - no client-only
// keys needed here. The legacy page's photographic <img> blocks
// (landing1.jpg etc.) are dropped rather than pointed at asset paths that
// don't exist in client/public/ yet (design.md's asset-migration note,
// same one AppNavbar.vue's avatarSrc comment flags) - the existing inline
// SVG icon components stand in for them.
definePageMeta({
  middleware: [
    () => {
      const authStore = useAuthStore()
      if (authStore.loggedIn) {
        return navigateTo('/dashboard')
      }
    },
  ],
})

const { t } = useI18n()
useHead({ title: t('client.app_name') })
</script>

<template>
  <div data-testid="home-page">
    <section class="container py-5 text-center">
      <h1 data-testid="home-title">{{ t('landing.title') }}</h1>
      <p class="lead" data-testid="landing-intro">{{ t('landing.intro') }}</p>

      <div class="d-flex justify-content-center flex-wrap gap-3 mb-5">
        <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-join">
          {{ t('landing.join') }}
        </NuxtLink>
        <NuxtLink to="/login" class="btn btn-outline-primary" data-testid="landing-login">
          {{ t('landing.login') }}
        </NuxtLink>
      </div>

      <div class="d-flex justify-content-center flex-wrap landing-icons" data-testid="landing-icons">
        <IconFixometer />
        <IconWiki />
        <IconGroups />
        <IconTalk />
        <IconEvents />
      </div>
    </section>

    <section class="container py-4">
      <div class="row g-4">
        <div class="col-12 col-md-4">
          <h2 data-testid="landing-learn-heading">{{ t('landing.learn') }}</h2>
          <ul>
            <li>{{ t('landing.repair_skills') }}</li>
            <li>{{ t('landing.repair_advice') }}</li>
            <li>{{ t('landing.repair_group') }}</li>
          </ul>
          <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-repair-start">
            {{ t('landing.repair_start') }}
          </NuxtLink>
        </div>

        <div class="col-12 col-md-4">
          <h2 data-testid="landing-organise-heading">{{ t('landing.organise') }}</h2>
          <ul>
            <li>{{ t('landing.organise_advice') }}</li>
            <li>{{ t('landing.organise_manage') }}</li>
            <li>{{ t('landing.organise_publicise') }}</li>
          </ul>
          <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-organise-start">
            {{ t('landing.organise_start') }}
          </NuxtLink>
        </div>

        <div class="col-12 col-md-4">
          <h2 data-testid="landing-campaign-heading">{{ t('landing.campaign') }}</h2>
          <ul>
            <li>{{ t('landing.campaign_join') }}</li>
            <li>{{ t('landing.campaign_barriers') }}</li>
            <li>{{ t('landing.campaign_data') }}</li>
          </ul>
          <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-campaign-start">
            {{ t('landing.campaign_start') }}
          </NuxtLink>
        </div>
      </div>

      <hr class="my-5">

      <div class="text-center">
        <h2 data-testid="landing-need-more-heading">{{ t('landing.need_more') }}</h2>
        <div class="row mt-4 text-start">
          <div class="col-12 col-md-6">
            <h3>{{ t('landing.network') }}</h3>
            <p>{{ t('landing.network_blurb') }}</p>
          </div>
          <div class="col-12 col-md-6">
            <ul>
              <li>{{ t('landing.network_tools') }}</li>
              <li>{{ t('landing.network_events') }}</li>
              <li>{{ t('landing.network_record') }}</li>
              <li>{{ t('landing.network_impact') }}</li>
              <li>{{ t('landing.network_brand') }}</li>
              <li>{{ t('landing.network_power') }}</li>
            </ul>
          </div>
        </div>
        <a
          href="https://therestartproject.org/contact/"
          target="_blank"
          rel="noopener noreferrer"
          class="btn btn-primary mt-3"
          data-testid="landing-network-start"
        >
          {{ t('landing.network_start') }}
        </a>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
.landing-icons > * {
  width: 2.5rem;
  height: auto;
  margin: 0 1rem 1rem;
}
</style>
