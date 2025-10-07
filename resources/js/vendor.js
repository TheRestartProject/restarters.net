// jQuery, moment, Select2, and Bootstrap are loaded from CDN (not bundled due to compatibility issues)
// All vendor libraries below expect jQuery to be available globally

// Import tinysort
import tinysort from 'tinysort';
window.tinysort = tinysort;

// Import slick-carousel (requires jQuery)
import 'slick-carousel';

// Import bootstrap-tokenfield (requires jQuery)
import 'bootstrap-tokenfield';

// Import bootstrap-sortable (requires jQuery, tinysort, moment)
import './bootstrap-sortable.js';

// Import ekko-lightbox (requires jQuery)
import 'ekko-lightbox';

// Import tempusdominus-bootstrap-4 (requires jQuery and moment)
import 'tempusdominus-bootstrap-4';
