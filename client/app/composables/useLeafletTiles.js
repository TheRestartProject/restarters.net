import { LEAFLET_TILES } from '../utils/mapConstants.js'

/**
 * The CARTO tile URL with the API key appended when one is configured.
 *
 * The key is public by nature - it is served to every visitor - but it is kept
 * in runtime config rather than the source so it can differ per environment.
 * runtimeConfig.public is resolved by Nitro at request time (see Dockerfile.fly:
 * "apiBase is runtime-overridable ... so no API URL is baked"), which matters
 * because the image is built long before the deployed environment's secrets
 * exist.
 *
 * Without a key the tiles still render, just watermarked, so an unconfigured
 * environment degrades rather than breaking.
 */
export function useLeafletTiles() {
  const key = useRuntimeConfig().public.cartoApiKey

  return key ? `${LEAFLET_TILES}?key=${encodeURIComponent(key)}` : LEAFLET_TILES
}
