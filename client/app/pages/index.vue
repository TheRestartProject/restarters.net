<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'
import IconFixometer from '~/components/icons/IconFixometer.vue'
import IconWiki from '~/components/icons/IconWiki.vue'
import IconGroups from '~/components/icons/IconGroups.vue'
import IconTalk from '~/components/icons/IconTalk.vue'
import IconEvents from '~/components/icons/IconEvents.vue'

// Port of the legacy resources/views/landing.blade.php marketing page. Logged-in
// visitors land here transiently on refresh/bookmark of "/" and are sent to the
// dashboard (design.md §6.2 section 9). Anonymous visitors see the marketing
// content, reusing the generated `landing.*` lang keys verbatim.
//
// This mirrors the live site 1:1: a hero grid (nav icons around the title,
// collapsed on mobile), three colour panels (gold/teal/pink) each with a photo,
// icon-prefixed hanging-indent lines and a CTA, a dashed rule, then the purple
// "empower your network" panel. Panels use the signature hard offset drop-shadow.
definePageMeta({
  // The live landing uses the plain header (brand mark + login/join), not the
  // full app navbar (layouts/header_plain.blade.php).
  layout: 'plain',
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
  <section class="landing-page" data-testid="home-page">
    <div class="container">
      <div class="landing-layout">
        <div class="landing-top-left"><IconFixometer /></div>
        <div class="landing-top-middle">
          <h1 data-testid="home-title">{{ t('landing.title') }}</h1>
        </div>
        <div class="landing-top-right"><IconWiki /></div>

        <div class="landing-middle-left"><IconGroups /></div>
        <div class="landing-middle-middle">
          <div class="textlarge" data-testid="landing-intro">{{ t('landing.intro') }}</div>
        </div>
        <div class="landing-middle-right"><IconTalk /></div>

        <div class="landing-bottom-left"><IconEvents /></div>
        <div class="landing-bottom-middle">
          <div>
            <NuxtLink to="/user/register" class="btn btn-primary me-3" data-testid="landing-join">
              {{ t('landing.join') }}
            </NuxtLink>
            <NuxtLink to="/login" class="btn btn-primary ms-3" data-testid="landing-login">
              {{ t('landing.login') }}
            </NuxtLink>
          </div>
        </div>
        <div class="landing-bottom-right" />
      </div>

      <div class="row mt-5">
        <div class="col-12 col-md-8 offset-md-2">
          <div class="landing-section has-background-gold">
            <img src="/images/landing/landing1.jpg" alt="Repair Skills (credit Mark Phillips)" class="d-none d-md-block">
            <div>
              <h2 data-testid="landing-learn-heading">{{ t('landing.learn') }}</h2>
              <p><img class="landing-icon" src="/images/landing/icon-book.svg" alt=""> {{ t('landing.repair_skills') }}</p>
              <p><img class="landing-icon" src="/images/landing/icon-chat-bubble.svg" alt=""> {{ t('landing.repair_advice') }}</p>
              <p><img class="landing-icon" src="/images/landing/icon-group.svg" alt=""> {{ t('landing.repair_group') }}</p>
              <div class="d-flex justify-content-around justify-content-md-start">
                <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-repair-start">
                  {{ t('landing.repair_start') }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-12 col-md-8 offset-md-2">
          <div class="landing-section has-background-teal justify-content-between">
            <div>
              <h2 data-testid="landing-organise-heading">{{ t('landing.organise') }}</h2>
              <p><img class="landing-icon" src="/images/landing/icon-chat-bubble.svg" alt=""> {{ t('landing.organise_advice') }}</p>
              <p><img class="landing-icon" src="/images/landing/icon-group.svg" alt=""> {{ t('landing.organise_manage') }}</p>
              <p><img class="landing-icon" src="/images/landing/icon-drill.svg" alt=""> {{ t('landing.organise_publicise') }}</p>
              <div class="d-flex justify-content-around justify-content-md-start">
                <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-organise-start">
                  {{ t('landing.organise_start') }}
                </NuxtLink>
              </div>
            </div>
            <img src="/images/landing/landing2.jpg" alt="Restart Party (credit Mark Phillips)" class="d-none d-md-block">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12 col-md-8 offset-md-2 mt-4">
          <div class="landing-section has-background-pink">
            <img src="/images/landing/landing3.jpg" alt="Restart Crowd (credit Mark Phillips)" class="d-none d-md-block">
            <div>
              <h2 data-testid="landing-campaign-heading">{{ t('landing.campaign') }}</h2>
              <p><img class="landing-icon" src="/images/landing/icon-group.svg" alt=""> {{ t('landing.campaign_join') }}</p>
              <p><img class="landing-icon" src="/images/landing/icon-book.svg" alt=""> {{ t('landing.campaign_barriers') }}</p>
              <p><img class="landing-icon" src="/images/landing/icon-microscope.svg" alt=""> {{ t('landing.campaign_data') }}</p>
              <div class="d-flex justify-content-around justify-content-md-start">
                <NuxtLink to="/user/register" class="btn btn-primary" data-testid="landing-campaign-start">
                  {{ t('landing.campaign_start') }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12 col-md-8 offset-md-2 mt-4">
          <hr class="landing-hr">
        </div>
      </div>

      <div class="row">
        <div class="col-12 col-md-8 offset-md-2 mt-4">
          <h1 data-testid="landing-need-more-heading">{{ t('landing.need_more') }}</h1>
          <div class="landing-section has-background-purple mt-4 mb-4">
            <div class="d-flex justify-content-between flex-wrap w-100">
              <div class="network-left">
                <div class="d-flex flex-column justify-content-between h-100">
                  <div class="flex-grow-1">
                    <h2>{{ t('landing.network') }}</h2>
                    <p class="noindent">{{ t('landing.network_blurb') }}</p>
                  </div>
                  <div class="d-none d-md-block">
                    <a
                      href="https://therestartproject.org/contact/"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="btn btn-primary"
                      data-testid="landing-network-start"
                    >{{ t('landing.network_start') }}</a>
                  </div>
                </div>
              </div>
              <div class="network-right">
                <p><img class="landing-icon" src="/images/landing/icon-chat-bubble.svg" alt=""> {{ t('landing.network_tools') }}</p>
                <p><img class="landing-icon" src="/images/landing/icon-cal.svg" alt=""> {{ t('landing.network_events') }}</p>
                <p><img class="landing-icon" src="/images/landing/icon-drill.svg" alt=""> {{ t('landing.network_record') }}</p>
                <p><img class="landing-icon" src="/images/landing/icon-microscope.svg" alt=""> {{ t('landing.network_impact') }}</p>
                <p><img class="landing-icon" src="/images/landing/icon-group.svg" alt=""> {{ t('landing.network_brand') }}</p>
                <p class="mb-0"><img class="landing-icon" src="/images/landing/icon-book.svg" alt=""> {{ t('landing.network_power') }}</p>
                <div class="d-flex d-md-none mt-2 justify-content-around">
                  <a
                    href="https://therestartproject.org/contact/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-primary"
                  >{{ t('landing.network_start') }}</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped lang="scss">
// Ported verbatim from the legacy landing.blade.php inline styles (colours,
// hard offset shadow, hero grid, hanging-indent icon lines). Literal hex to
// match the live site exactly; scoped SCSS here has no access to the global
// brand variables (no additionalData is configured).
.landing-page {
  padding-top: 31px;
}

.landing-page h1,
.landing-page h2 {
  font-weight: bold;
}

.landing-page h1 {
  line-height: 1.1;
}

.textlarge {
  font-size: 20px;
}

.landing-section {
  display: flex;
  background-color: #fff;
  border: 1px solid black;
  box-shadow: 6px 6px 0 0 black;

  img {
    max-height: 250px;
    object-fit: cover;
  }

  > div {
    padding: 20px;
  }

  .landing-icon {
    max-height: 30px;
    width: 30px;
    margin-right: 5px;
  }

  p:not(.noindent) {
    padding-left: 39px;
    text-indent: -39px;
  }
}

.has-background-gold { background-color: #ffbe5f; }
.has-background-teal { background-color: #4aaebc; }
.has-background-pink { background-color: #f49292; }
.has-background-purple { background-color: #80a4e0; }

.landing-hr {
  border-top: 3px dashed black;
}

.network-left {
  width: 45%;
  padding-right: 1rem;
}

.network-right {
  width: 55%;
  padding-left: 1rem;
}

@media only screen and (max-width: 767px) {
  .network-left,
  .network-right {
    width: 100%;
    padding-right: 0;
    padding-left: 0;
  }
}

// Hero: title centred with the five nav icons arranged around it on desktop;
// on mobile the side columns collapse to 0 so only the title/intro/CTAs show.
.landing-layout {
  display: grid;
  grid-template-rows: 1fr 1fr 1fr;
  grid-template-columns: 1fr 1fr 1fr;
  column-gap: 30px;
  align-items: center;

  svg {
    width: 30px;
    height: auto;
  }
}

.landing-top-left    { grid-area: 1 / 1 / 2 / 2; display: flex; justify-content: end; }
.landing-top-middle  { grid-area: 1 / 2 / 2 / 3; text-align: center; }
.landing-top-right   { grid-area: 1 / 3 / 2 / 4; display: flex; justify-content: start; }
.landing-middle-left { grid-area: 2 / 1 / 3 / 2; display: flex; justify-content: end; padding-right: 30px; }
.landing-middle-middle { grid-area: 2 / 2 / 3 / 3; text-align: center; }
.landing-middle-right { grid-area: 2 / 3 / 3 / 4; display: flex; justify-content: start; padding-left: 30px; }
.landing-bottom-left { grid-area: 3 / 1 / 4 / 2; display: flex; justify-content: end; margin-top: 10px; }
.landing-bottom-middle { grid-area: 3 / 2 / 4 / 3; display: flex; justify-content: center; margin-top: 10px; }
.landing-bottom-right { grid-area: 3 / 3 / 4 / 4; }

@media only screen and (max-width: 767px) {
  .landing-layout {
    grid-template-columns: 0 1fr 0;
    margin-top: 20px;
    text-align: center;
  }
}
</style>
