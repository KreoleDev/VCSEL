const selectProductPage = Vue.component('selectProductPage', function (resolve, reject) {
    resolve({
        store,
        template: `
        <div>
            <div id="loadingSlate" v-show="showLoading">
                <div></div>
            </div>
            <div id="loadingMessage" v-show="showLoading">{{ loadingMessage }}</div>

            <div id="errorSlate" v-show="showError">
                <div id="errorGraphic"></div>
                <div id="errorMessage">{{ errorMessage }}</div>
            </div>

            <div id="mainContent">
                <h1>Select a product to configure and test:</h1>
                <div id="productsList">
                    <div v-for="product in products" v-on:click="listTLAs(product.product_id, product.title)" class="productContainer">
                        <div class="productImg" v-bind:style="{ backgroundImage: 'url(' + product.img_filename + ')'}"></div>
                        <div class="productTitle">{{ product.title }}</div>
                    </div>
                </div>
                <div id="appVersion">Version: {{ appVersion }} | P/N: 109118A</div>
            </div>
        </div>
        `,
        data: function() {
            return {
                appVersion : APP_VERSION,
                showLoading: true,
                loadingMessage: 'Checking application version...',
                showError: false,
                errorMessage: '',
                products: []
            }
        },
        mounted: function() {
            var self = this;

            aniShowLoading().then(() => {
                this.connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);
                //Check version number
                this.connectionInst.postToServer({
                    'mode': 'getCurrentVersion'
                }, 'version/').then((response) => {
                    var convertedVersion = parseInt(self.appVersion.replace(/\./g,""));
                    var convertedResponseVersion = parseInt(response.data.replace(/\./g,""));
                    if(convertedVersion >= convertedResponseVersion) {
                        self.loadingMessage = 'Getting products list from server...';
                        //Get list of products from server
                        this.connectionInst.postToServer({
                            'mode': 'getActiveProducts'
                        }, 'products/').then((response) => {
                            self.products = response.data;
                            aniHideLoading().then(() => {
                                self.showLoading = false;
                            }, () => { });
                        }, () => {
                            //Server communications error
                            self.errorMessage = "Cannot communicate with server address: " + CFG_DATA_SERVICE_URL + 'products/';
                            self.showError = true;
                            aniShowError().then(() => { }, () => { });
                        });
                    } else {
                        //Version mis match
                        self.errorMessage = "Wrong version: expected " + response.data + " this version is " + self.appVersion;
                        self.showError = true;
                        aniShowError().then(() => { }, () => { });
                    }
                    
                }, () => {
                    //Server communications error
                    self.errorMessage = "Cannot communicate with server address: " + CFG_DATA_SERVICE_URL + 'version/';
                    self.showError = true;
                    aniShowError().then(() => { }, () => { });
                }); 
            }, () => { });
        },
        methods: {
            //------------------------------------------------------------------------------------------------------
            listTLAs: function(productID, productName) {
                var self = this;
                store.set('selectedProduct', { productID, productName });
                aniUnloadPage().then(() => {
                    self.$router.push('/select-tla');
                }, () => { });
            }
            //------------------------------------------------------------------------------------------------------
        }
    });
});