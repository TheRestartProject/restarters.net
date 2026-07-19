import SessionAPI from './SessionAPI.js'
import AuthAPI from './AuthAPI.js'
import GroupAPI from './GroupAPI.js'
import EventAPI from './EventAPI.js'
import DeviceAPI from './DeviceAPI.js'
import UserAPI from './UserAPI.js'
import RoleAPI from './RoleAPI.js'
import NetworkAPI from './NetworkAPI.js'
import DashboardAPI from './DashboardAPI.js'
import MapsAPI from './MapsAPI.js'
import ConfigAPI from './ConfigAPI.js'
import BrandAPI from './BrandAPI.js'
import SkillAPI from './SkillAPI.js'
import GroupTagAPI from './GroupTagAPI.js'
import CategoryAPI from './CategoryAPI.js'
import PreviewDeployAPI from './PreviewDeployAPI.js'
import ModerationAPI from './ModerationAPI.js'
import TalkAPI from './TalkAPI.js'
import AlertsAPI from './AlertsAPI.js'

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
  role: new RoleAPI(config),
  network: new NetworkAPI(config),
  dashboard: new DashboardAPI(config),
  maps: new MapsAPI(config),
  config: new ConfigAPI(config),
  brand: new BrandAPI(config),
  skill: new SkillAPI(config),
  groupTag: new GroupTagAPI(config),
  category: new CategoryAPI(config),
  previewDeploy: new PreviewDeployAPI(config),
  moderation: new ModerationAPI(config),
  talk: new TalkAPI(config),
  alerts: new AlertsAPI(config),
})
