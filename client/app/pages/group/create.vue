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
    <div class="row justify-content-center">
      <div class="col-lg-12">
        <!-- resources/views/group/create.blade.php's b-card.box (findings/
             parity-v2/group-forms.md #2): white bg, hard offset
             drop-shadow, 1px solid black border, square corners. -->
        <div class="group-create-box">
          <h1>{{ t('general.new_group') }}</h1>
          <p>{{ t('groups.add_groups_content') }}</p>

          <GroupForm @created="onCreated" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.group-create-box {
  background-color: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px #222;
  border-radius: 0;
  margin-top: 1.5rem;
  padding: 1.5rem;
}
</style>
