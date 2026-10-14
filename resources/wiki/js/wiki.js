import jquery from 'jquery';
window.$ = window.jQuery = jquery;

// Static import, not require(): Vite's commonjs transform only covers
// node_modules, so a bare require() here survives into the browser bundle
// and throws. Bootstrap 4's JS attaches its jQuery plugins on import;
// import * gives the plain namespace object the wiki skin expects.
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
