import Vue from "vue";
import Vuex from "vuex";
import attendanceModule from './attendance';
import volunteersModule from './volunteers';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    attendance: attendanceModule,
    volunteers: volunteersModule
  },
});