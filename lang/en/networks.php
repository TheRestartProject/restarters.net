<?php

return [
  'networks' => 'Networks',
  'network' => 'Network',
  'general' => [
    'networks' => 'Networks',
    'network' => 'Network',
    'particular_network' => ':networkName network',
    'groups' => 'Groups',
    'about' => 'About',
    'count' => '{0} There are currently no groups in the :name network.|{1} There is currently :count group in the :name network. <a href="/group/network/:id">View this group</a>.|[2,*] There are currently :count groups in the :name network. <a href="/group/network/:id">View these groups</a>.',
    'actions' => 'Network Actions',
    'coordinators' => 'Network Coordinators',
    'coordinator_badge' => 'Coordinator',
    'impact' => 'Impact'
  ],
  'stats' => [
    'groups' => 'Groups',
    'events' => 'Events',
    'waste_diverted' => 'Waste Diverted',
    'co2_prevented' => 'CO2 Prevented'
  ],
  'tags' => [
    'title' => 'Group Tags',
    'no_tags' => 'No tags created yet.',
    'new_tag_placeholder' => 'New tag name...',
    'create' => 'Create Tag',
    'create_error' => 'Failed to create tag. Please try again.',
    'delete' => 'Delete',
    'delete_confirm_title' => 'Delete Tag',
    'delete_confirm_message' => 'Are you sure you want to delete the tag ":name"?',
    'delete_warning' => '{1} This will remove the tag from :count group.|[2,*] This will remove the tag from :count groups.'
  ],
  'index' => [
    'your_networks' => 'Your networks',
    'your_networks_explainer' => 'These are the networks for which you are a coordinator.',
    'your_networks_no_networks' => 'You are not a coordinator of any networks.',
    'all_networks' => 'All networks',
    'all_networks_explainer' => 'All networks in the system (admin-only).',
    'all_networks_no_networks' => 'There are no networks in the system.',
    'description' => 'Description',
  ],
  'show' => [
    'about_modal_header' => 'About :name',
    'add_groups_menuitem' => 'Add groups',
    'add_groups_modal_header' => 'Add groups to :name',
    'add_groups_select_label' => 'Choose groups to add',
    'add_groups_save_button' => 'Add',
    'add_groups_warning_none_selected' => 'No groups selected.',
    'add_groups_success' => '{1} :number group added.|[2,*] :number groups added.',
    'view_groups_menuitem' => 'View groups',
    'groups_count' => '{0} There are currently no groups in the :name network.|{1} There is currently :count group in the :name network.|[2,*] There are currently :count groups in the :name network.',
    'view_groups_link' => 'View these groups.',
    'none' => 'None',
  ],
  'edit' => [
    'label_logo' => 'Network logo',
    'button_save' => 'Save changes',
    'add_new_field' => 'Add new field',
    'new_field_name' => 'New field name',
    'add_field' => 'Add field',
  ],
];
