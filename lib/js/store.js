const Store = require('./lib/js/current-state.js');

const store = new Store({
    // We'll call our data file 'current-state'
    configName: 'current-state',
    defaults: {
      selectedProduct: { productID: '', productName: '' },
      selectedTLA: { tlaID: '', tlaName: '' }
    }
});