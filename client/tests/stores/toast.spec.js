import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useToastStore } from '../../app/stores/toast.js'

describe('stores/toast', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('starts empty', () => {
    const store = useToastStore()
    expect(store.toasts).toEqual([])
  })

  it('push() adds a toast with defaults and returns its id', () => {
    const store = useToastStore()
    const id = store.push({ message: 'Saved' })

    expect(store.toasts).toHaveLength(1)
    expect(store.toasts[0]).toMatchObject({
      id,
      message: 'Saved',
      variant: 'info',
      timeout: 5000,
    })
  })

  it('error() surfaces an APIError-shaped object as a danger toast', () => {
    const store = useToastStore()
    store.error({ status: 422, data: { message: 'Validation failed' } })

    expect(store.toasts[0]).toMatchObject({
      message: 'Validation failed',
      variant: 'danger',
    })
  })

  it('error() surfaces a plain Error message as a danger toast', () => {
    const store = useToastStore()
    store.error(new Error('Network request failed'))

    expect(store.toasts[0]).toMatchObject({
      message: 'Network request failed',
      variant: 'danger',
    })
  })

  it('error() surfaces a plain string as a danger toast', () => {
    const store = useToastStore()
    store.error('Something specific went wrong')

    expect(store.toasts[0]).toMatchObject({
      message: 'Something specific went wrong',
      variant: 'danger',
    })
  })

  it('error() falls back to a generic message for an unrecognized shape', () => {
    const store = useToastStore()
    store.error({})

    expect(store.toasts[0]).toMatchObject({
      message: 'Something went wrong. Please try again.',
      variant: 'danger',
    })
  })

  it('success() pushes a success-variant toast', () => {
    const store = useToastStore()
    store.success('Group updated')

    expect(store.toasts[0]).toMatchObject({
      message: 'Group updated',
      variant: 'success',
    })
  })

  it('dismiss() removes only the matching toast', () => {
    const store = useToastStore()
    const id1 = store.push({ message: 'One' })
    const id2 = store.push({ message: 'Two' })

    store.dismiss(id1)

    expect(store.toasts).toHaveLength(1)
    expect(store.toasts[0].id).toBe(id2)
  })

  it('clear() empties the toast queue', () => {
    const store = useToastStore()
    store.push({ message: 'One' })
    store.push({ message: 'Two' })

    store.clear()

    expect(store.toasts).toEqual([])
  })
})
