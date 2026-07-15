<script setup>
import { useI18n } from 'vue-i18n'
import GroupForm from '~/components/groups/GroupForm.vue'

// /group/create - resources/views/group/create.blade.php +
// GroupAddEditPage.vue (design.md §6.2 B6 task brief). The Blade page
// passes no can-approve/can-network/can-edit-tags props at all, so the
// admin-only panel (networks/tags/area/moderate) never appears here -
// GroupForm.vue only shows it in edit mode.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('general.new_group') })

// GroupCreateTest.php::testCreate + legacy window.location = '/group/edit/'
// + id: on success the user lands on the edit page for the group they just
// created (design.md §6.2 B6 task brief point 4 - "legacy behaviour
// Playwright expects: URL **/edit/**").
async function onCreated(id) {
  await navigateTo(`/group/edit/${id}`)
}
</script>

<template>
  <div class="container py-4" data-testid="group-create-page">
    <h1>{{ t('general.new_group') }}</h1>
    <p>{{ t('groups.add_groups_content') }}</p>
    <p class="text-muted">{{ t('groups.groups_approval_text') }}</p>

    <GroupForm @created="onCreated" />
  </div>
</template>
