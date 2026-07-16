import SessionAPI from './SessionAPI.js'
import AuthAPI from './AuthAPI.js'
import GroupAPI from './GroupAPI.js'
import EventAPI from './EventAPI.js'
import DeviceAPI from './DeviceAPI.js'
import UserAPI from './UserAPI.js'
import NetworkAPI from './NetworkAPI.js'
import DashboardAPI from './DashboardAPI.js'
import MapsAPI from './MapsAPI.js'
import ConfigAPI from './ConfigAPI.js'

/**
 * Factory: one instance of each resource class, sharing the same config
 * (base URL + token/locale getters). Consumed by plugins/api.ts, which
 * provides the result as $api.
 */
export default (config) => ({
  session: new SessionAPI(config),
  auth: new AuthAPI(config),
  group: new GroupAPI(config),
  event: new EventAPI(config),
  device: new DeviceAPI(config),
  user: new UserAPI(config),
  network: new NetworkAPI(config),
  dashboard: new DashboardAPI(config),
  maps: new MapsAPI(config),
  config: new ConfigAPI(config),
})
