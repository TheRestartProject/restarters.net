<script setup>
import { useI18n } from 'vue-i18n'
import { claimInvite } from '~/composables/useInviteClaim.js'

definePageMeta({ layout: 'plain' })

const { t } = useI18n()
useHead({ title: t('client.invite.title') })

const route = useRoute()

await claimInvite({
  code: route.params.code,
  inviteType: 'group',
  viewPathPrefix: '/group/view/',
  joinedMessage: t('groups.invite_confirmed'),
  currentPath: route.fullPath,
})
</script>

<template>
  <div class="container py-5" data-testid="group-invite-page">
    <p>{{ t('client.invite.processing') }}</p>
  </div>
</template>
