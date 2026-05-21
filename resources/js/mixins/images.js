// Mixin to handle static image URLs in Vue templates
// 
// Vite scans Vue templates during build and tries to resolve any src="/path/to/image" 
// as module imports, which causes build errors for static assets in the public directory.
// 
// This mixin provides a imageUrl() function that returns the path at runtime,
// preventing Vite from treating static asset paths as module imports during build.
//
// Usage: :src="imageUrl('/images/my-image.svg')" instead of src="/images/my-image.svg"

export default {
  computed: {
    imageUrl() {
      return (path) => path
    }
  }
}