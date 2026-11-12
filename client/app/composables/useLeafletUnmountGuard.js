import { onErrorCaptured } from 'vue'

export const MAP_CONTAINER_GONE = 'Map container not found.'

// vue-leaflet's <LMap> creates the Leaflet map a few awaits into its mounted
// hook. If it's unmounted in the meantime - a page that re-renders while it
// loads, or a redirect straight after saving - it hands Leaflet its own root
// element, which is gone, and Leaflet throws "Map container not found.".
// While a page is first loading Nuxt shows that as its error page. Nothing is
// lost, as that map was on its way out, so the error stops here. Any other
// error carries on as usual.
export function useLeafletUnmountGuard() {
  onErrorCaptured((err) => (err?.message === MAP_CONTAINER_GONE ? false : undefined))
}
