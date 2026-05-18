const selectTLAPage = Vue.component('selectTLAPage', function (resolve, reject) {
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

                <div id="errorPrompt" v-show="showErrorPrompt">
                    <div class="errorWindow">
                        {{ errorPromptMessage }}
                        <button v-on:click="closeErrorPrompt()">OK</button>
                    </div>
                </div>

                <div id="mainContent">
                    <nav id="breadcrumbs">
                        <ol>
                            <li><router-link to="/"><strong>Product:</strong> {{ productName }}</router-link></li>
                        </ol>
                    </nav>
                    <h1>Enter tester name:</h1>
                    <div class="padded">
                        <input type="text" v-model="testerName" placeholder="your name">
                    </div>

                    <h1>Select a TLA:</h1>
                    <div class="padded">
                        <div style="float:left; width:48%;">
                            <select v-model="tlaID" v-on:change="getTLAInfo()">
                                <option disabled value="">Please select one</option>
                                <option v-for="tla in tlas" v-bind:value="tla.tla_id">{{ tla.tla_number }}</option>
                            </select>
                        </div>
                        <div style="float:right; width:48%; line-height: 1.4em;">
                            <div class="boxedIn">
                                <h2>TLA Info</h2>
                                <table>
                                    <tr v-for="tlaItem in tlaInfo">
                                        <td><strong>{{ tlaItem.title }}:</strong></td> 
                                        <td>{{ tlaItem.value }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>

                    <nav id="bottomNav">
                        <div style="float:left;">
                            <button v-on:click="$router.push('/')">&lt; Back to Products</button>
                        </div>
                        <div style="float:right;">
                            <button v-on:click="loadTest()">Next &gt;</button>
                        </div>
                        <div style="clear:both;"></div>
                        
                    </nav>
                </div>
            </div>
        `,
        data: function () {
            return {
                productName: '',
                showLoading: true,
                loadingMessage: 'Getting list of TLAs for this product...',
                showError: false,
                errorMessage: '',
                showErrorPrompt: false,
                errorPromptMessage: '',
                tlas: [],
                tlaID: '',
                tlaName: '',
                tlaInfo: [],
                testerName: ''
            }
        },
        mounted: function() {
            var self = this;
            self.testerName = window.app.testerName;
            let selectedProduct = store.get('selectedProduct');
            self.productName = selectedProduct.productName;
            aniShowLoading().then(() => {
                //Get list of tlas from server
                this.connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);
                this.connectionInst.postToServer({
                    'mode': 'getActiveTLAs',
                    'product_id': selectedProduct.productID
                }, 'tlas/').then((response) => {
                    self.tlas = response.data;

                    var setTLA = store.get('selectedTLA');
                    self.tlaID = setTLA.tlaID;
                    self.tlaName = setTLA.tlaName;
                    if(self.tlaID > 0) {
                        self.getTLAInfo();
                    }

                    aniHideLoading().then(() => {
                        self.showLoading = false;
                    }, () => { });
                }, () => {
                    //Server communications error
                    self.errorMessage = "Cannot communicate with server address: " + CFG_DATA_SERVICE_URL + 'tlas/';
                    self.showError = true;
                    aniShowError().then(() => { }, () => { });
                });
            }, () => { });
        },
        methods: {
            //------------------------------------------------------------------------------------------------------
            loadTest: function() {
                var self = this;

                if(self.testerName != ''){
                    window.app.testerName = self.testerName;
                    if(self.tlaID != '') {
                        //find tlaName
                        for(var i=0; i < self.tlas.length; i++) {
                            if(self.tlas[i].tla_id == self.tlaID) {
                                self.tlaName = self.tlas[i].tla_number;
                            }
                        }
                        store.set('selectedTLA', { tlaID: self.tlaID, tlaName: self.tlaName });

                        aniUnloadPage().then(() => {
                            self.$router.push('/select-mode');
                        }, () => { });
                    } else {
                        //TLA needs selected
                        self.errorPromptMessage = 'Please select a TLA first!';
                        self.showErrorPrompt = true;
                        aniShowErrorPrompt().then(() => { }, () => { });
                    }
                } else {
                    //Need tester name selected
                    self.errorPromptMessage = 'Please include your name!';
                    self.showErrorPrompt = true;
                    aniShowErrorPrompt().then(() => { }, () => { });
                }
            },
            //------------------------------------------------------------------------------------------------------
            closeErrorPrompt: function() {
                var self = this;
                aniHideErrorPrompt().then(() => {
                    self.showErrorPrompt = false;
                }, () => { });
            },
            //------------------------------------------------------------------------------------------------------
            getTLAInfo: function() {
                var self = this;
                console.log('TLA: ' + self.tlaID);
                this.connectionInst.postToServer({
                    'mode': 'getTLAInfo',
                    'tla_id': self.tlaID
                }, 'tlas/').then((response) => {
                    self.tlaInfo = response.data;
                }, () => {
                    //Server communications error
                    self.errorMessage = "Cannot communicate with server address: " + CFG_DATA_SERVICE_URL + 'tlas/';
                    self.showError = true;
                    aniShowError().then(() => { }, () => { });
                });
            },
            //------------------------------------------------------------------------------------------------------
        }
    });
});