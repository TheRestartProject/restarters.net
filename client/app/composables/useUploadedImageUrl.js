// Builds a browsable URL for an uploaded file (group/device/profile images,
// stored under Laravel's /uploads). Two shapes show up across the API today:
// GET /api/v2/groups/{id}'s `image` is a bare filename (relative to the API
// origin), while upload-confirmation responses (e.g. POST
// .../images -> image_url) already return an absolute URL via Laravel's
// url() helper. A bare relative path (`/uploads/...`) must NOT be used
// as-is in an <img> src: it resolves against whatever origin the Nuxt app
// is served from, not the Laravel API origin the file actually lives on -
// in dev that's the client's own SPA-fallback dev server, which serves the
// app shell for any unmatched path instead of 404ing, so the browser tries
// (and fails) to decode HTML as an image.
export function useUploadedImageUrl() {
  const runtimeConfig = useRuntimeConfig()

  function uploadedImageUrl(image) {
    if (!image) return null
    if (/^https?:\/\//i.test(image)) return image
    return `${runtimeConfig.public.apiBase}/uploads/${image}`
  }

  return { uploadedImageUrl }
}
