import Vue from 'vue'
import Vuex from "vuex";
import authModule from './auth';
import attendanceModule from './attendance';
import devicesModule from './devices';
import eventsModule from './events';
import groupsModule from './groups';
import volunteersModule from './volunteers';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    auth: authModule,
    attendance: attendanceModule,
    devices: devicesModule,
    events: eventsModule,
    groups: groupsModule,
    volunteers: volunteersModule
  },
});