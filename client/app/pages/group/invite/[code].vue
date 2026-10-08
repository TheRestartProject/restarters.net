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
  // develop names the group/event and links to it.
  joinedMessage: (data) =>
    t('groups.you_have_joined', { name: data.name, url: `/group/view/${data.id}` }),
  alreadyMemberMessage: (data) =>
    t('groups.already_member', { name: data.name, url: `/group/view/${data.id}` }),
  currentPath: route.fullPath,
})
</script>

<template>
  <div class="container py-5" data-testid="group-invite-page">
    <p>{{ t('client.invite.processing') }}</p>
  </div>
</template>
