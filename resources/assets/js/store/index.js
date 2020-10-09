import Vue from "vue";
import Vuex from "vuex";
import attendanceModule from './attendance';
import devicesModule from './devices';
import eventsModule from './events';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    attendance: attendanceModule,
    devices: devicesModule,
    events: eventsModule
  },
});