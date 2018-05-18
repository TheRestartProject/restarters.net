
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./jquery.min');
require('./bootstrap.min');
require('./bootstrap');

// Plugins
require('../../../node_modules/moment/min/locales.min.js');
require('../../../node_modules/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js');
require('../../../node_modules/summernote/dist/summernote.js');
require('../../../node_modules/summernote-cleaner/summernote-cleaner.js');
require('../../../node_modules/bootstrap-fileinput/js/fileinput.js');
require('../../../node_modules/chart.js/dist/Chart.min.js');
require('../../../node_modules/bootstrap-select/dist/js/bootstrap-select.min.js');

require('./main.js');
// require('./misc/geocoder.js');
// require('./misc/markers.js');

// window.Vue = require('vue');

// /**
//  * Next, we will create a fresh Vue application instance and attach it to
//  * the page. Then, you may begin adding components to this application
//  * or customize the JavaScript scaffolding to fit your unique needs.
//  */

// Vue.component('example-component', require('./components/ExampleComponent.vue'));

// const app = new Vue({
//     el: '#app'
// });
