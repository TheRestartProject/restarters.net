// Shared bootstrap-vue-next stubs for the group-view parity work (B5).
// Bootstrap-vue-next's real components (BDropdown/BTabs/BPopover in
// particular) rely on @floating-ui/vue positioning that isn't worth
// exercising in unit tests - these stubs render their slots directly and
// unconditionally, same spirit as the BButton/BModal stubs already used
// throughout tests/components/groups/*.spec.js, so tests can assert on
// content without simulating hover/click-to-open interactions.
export const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
export const BAlertStub = { template: '<div><slot /></div>' }
export const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
export const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
export const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /></div>',
}
export const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
export const BFormGroupStub = { template: '<div><slot /></div>' }

// GroupActions.vue / GroupVolunteers.vue's edit dropdown: renders the
// `text` prop and `button-content` slot as visible content, then the
// default slot (its BDropdownItem children) - always "open" for testing.
export const BDropdownStub = {
  props: ['text', 'variant', 'noCaret'],
  template: '<div v-bind="$attrs">{{ text }}<slot name="button-content" /><slot /></div>',
}
export const BDropdownItemStub = {
  props: ['to', 'href', 'disabled'],
  template: '<a v-bind="$attrs" :href="to || href" :class="{ disabled }"><slot /></a>',
}

// GroupDevicesBreakdown.vue's cluster tabs: every BTab's content renders
// unconditionally rather than only the active one.
export const BTabsStub = { template: '<div><slot /></div>' }
export const BTabStub = { props: ['title', 'active'], template: '<div><slot /></div>' }

// GroupStats.vue's environmental-impact info popover.
export const BPopoverStub = { props: ['target'], template: '<div><slot /></div>' }

export const GROUP_VIEW_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BBadge: BBadgeStub,
  BModal: BModalStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BDropdown: BDropdownStub,
  BDropdownItem: BDropdownItemStub,
  BTabs: BTabsStub,
  BTab: BTabStub,
  BPopover: BPopoverStub,
}
