import { afterEach, describe, expect, it, vi } from 'vitest'
import { useUploadedImageUrl } from '../../app/composables/useUploadedImageUrl.js'

describe('composables/useUploadedImageUrl', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.stubGlobal('useRuntimeConfig', () => ({ public: {} }))
  })

  function stubApiBase(apiBase) {
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase } }))
  }

  it('returns null for a falsy image', () => {
    stubApiBase('https://api.example.com')
    const { uploadedImageUrl } = useUploadedImageUrl()
    expect(uploadedImageUrl(null)).toBeNull()
    expect(uploadedImageUrl('')).toBeNull()
    expect(uploadedImageUrl(undefined)).toBeNull()
  })

  it('passes an already-absolute URL through unchanged', () => {
    stubApiBase('https://api.example.com')
    const { uploadedImageUrl } = useUploadedImageUrl()
    expect(uploadedImageUrl('https://cdn.example.com/x.png')).toBe('https://cdn.example.com/x.png')
    expect(uploadedImageUrl('http://cdn.example.com/x.png')).toBe('http://cdn.example.com/x.png')
  })

  it('prefixes a bare filename with apiBase + /uploads/', () => {
    stubApiBase('https://api.example.com')
    const { uploadedImageUrl } = useUploadedImageUrl()
    expect(uploadedImageUrl('mid_xyz.png')).toBe('https://api.example.com/uploads/mid_xyz.png')
  })

  it('prefixes an already /uploads/-rooted path with apiBase only, without doubling /uploads/', () => {
    stubApiBase('https://api.example.com')
    const { uploadedImageUrl } = useUploadedImageUrl()
    // api-contracts-phase-c.md C1b: attendee.profilePath arrives as
    // "/uploads/thumbnail_xyz.png" already.
    expect(uploadedImageUrl('/uploads/thumbnail_xyz.png')).toBe('https://api.example.com/uploads/thumbnail_xyz.png')
  })
})
