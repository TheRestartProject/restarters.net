import DeviceCategorySelect from './DeviceCategorySelect.vue'

describe('DeviceCategorySelect opening', () => {
  function context (value) {
    return {
      $emit: jest.fn(),
      $nextTick: (fn) => fn(),
      $refs: {
        multiselect: {
          value,
          trackBy: 'value',
          filteredOptions: [{ value: 1 }, { value: 2 }],
          pointerSet: jest.fn(),
          pointerPosition: 0,
          $refs: { list: {} },
        },
      },
    }
  }

  test('scrolls to the chosen category', () => {
    const ctx = context({ value: 2 })
    DeviceCategorySelect.methods.onOpen.call(ctx)

    expect(ctx.$emit).toHaveBeenCalledWith('open')
    expect(ctx.$refs.multiselect.pointerSet).toHaveBeenCalledWith(1)
  })

  test('copes with an item that has no category yet', () => {
    // It threw "Cannot read properties of null (reading 'value')" when a new item's category was opened.
    const ctx = context(null)

    expect(() => DeviceCategorySelect.methods.onOpen.call(ctx)).not.toThrow()
    expect(ctx.$emit).toHaveBeenCalledWith('open')
    expect(ctx.$refs.multiselect.pointerSet).not.toHaveBeenCalled()
  })
})
