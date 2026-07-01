// Stub for vue-awesome's Icon component (ES module export trips up babel-jest's
// CJS transformer when imported transitively from a Vue component under test).
module.exports = { name: 'fa-icon', template: '<span class="stub-fa-icon" />' }
module.exports.default = module.exports
