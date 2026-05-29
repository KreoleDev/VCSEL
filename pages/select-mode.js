window.selectModePage = Vue.component('selectModePage', function (resolve, reject) {
    resolve({
        store,
        template: `
            <div>
                <div id="imgContainerLarge" v-if="showDisplayImgLarge" v-on:click="hideLargeImg()" v-bind:style="{ backgroundImage: window.app.displayImg }"></div>
                <div id="loadingSlate" v-show="showLoading">
                    <div></div>
                </div>
                <div id="loadingMessage" v-show="showLoading">{{ loadingMessage }}</div>

                <div id="stateIndicator" v-show="window.app.showStateIndicator">
                    <div>{{ window.app.stateIndicatorMessage }}</div>
                </div>

                <div id="errorSlate" v-show="window.app.showError">
                    <div id="errorGraphic"></div>
                    <div id="errorMessage">{{ window.app.errorMessage }}</div>
                </div>

                <div id="errorPrompt" v-show="window.app.showErrorPrompt">
                    <div class="errorWindow">
                        {{ window.app.errorPromptMessage }}
                        <button v-on:click="closeErrorPrompt()">OK</button>
                    </div>
                </div>

                <div id="mainContent" v-bind:style="{ color: (mode=='production'?'#000':'#900') }">
                    <button class="logoutButton" v-on:click="window.app.logout()">Logout</button>
                    <nav id="breadcrumbs">
                        <ol>
                            <li><router-link v-bind:class="{ disabled: !window.app.allowBack }" to="/"><strong>Product:</strong> {{ productName }}</router-link></li>
                            <li><router-link v-bind:class="{ disabled: !window.app.allowBack }" to="/select-tla"><strong>TLA:</strong> {{ tlaName }}</router-link></li>
                        </ol>
                    </nav>
                    <div id="sidebar">
                        <div class="sidebarInner" v-for="test in window.app.tests" v-on:click="activateTest(test.test_id)" v-bind:class="{ activeBtns: mode != 'production' }">
                            <div class="testProgressIndicator" v-bind:class="{ isCurrent: test.is_current == '1', passedTest: test.passed_test == 'true' }"></div>
                            <div class="testTitle">{{ test.title }}</div>
                            <div style="clear:both;"></div>
                        </div>
                    </div>
                    <div id="testWindow">
                        <div class="padded">
                            <div class="instructions">{{ window.app.instructions }}<div id="altInstructions"></div></div>
                            <div class="resultsWindow" v-show="window.app.showResultText || window.app.showDisplayImg">
                                <div class="resultTextContainer" v-show="window.app.showResultText" v-bind:class="{ passState: window.app.resultState=='pass', failState: window.app.resultState=='fail' }">{{ window.app.resultText }}<div id="altResults"></div></div>
                                <div class="imgContainer" v-on:click="showLargeImg()" v-show="window.app.showDisplayImg" v-bind:style="{ backgroundImage: window.app.displayImg }"></div>
                            </div>
                            <div id="vcselCameraPanel">
                                <div id="vcselCameraStage" v-bind:class="{ cameraReady: window.app.vcselCameraReady }">
                                    <video id="vcselCameraVideo" autoplay muted playsinline></video>
                                    <canvas id="vcselCameraOverlay"></canvas>
                                    <div id="vcselCameraReadout">
                                        <span>Brightness: {{ formatCameraValue(window.app.vcselCameraBrightness) }}</span>
                                        <span>Area: {{ formatCameraValue(window.app.vcselCameraArea) }}</span>
                                        <span>{{ window.app.vcselCameraStatus }}</span>
                                    </div>
                                    <div id="vcselCameraSaved" v-show="window.app.vcselCameraSavedMessage">{{ window.app.vcselCameraSavedMessage }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="padded">
                            <nav id="testNav">
                                <table>
                                    <tr>
                                        <td><button v-on:click="userFailTest()" v-show="window.app.passFailVisible">Fail</button></td>
                                        <td><button v-on:click="userPassTest()" v-show="window.app.passFailVisible">Pass</button></td>
                                        <td></td>
                                        <td><button v-on:click="retryTest()"  v-show="window.app.retryVisible">Retry Test</button></td>
                                        <td><button v-on:click="nextTest()" v-show="window.app.nextTestVisible">{{nextTestText}}</button></td>
                                    </tr>
                                </table>
                            </nav>
                        </div>
                    </div>

                    <nav id="bottomNav">
                        <div style="float:left;">
                            <button v-on:click="$router.push('/select-tla')" v-show="window.app.allowBack">&lt; Back to TLAs</button>
                        </div>
                        <div style="float:right;">
                            <button v-show="showAuto" v-on:click="switchAuto()">{{autoAdvanceBtnText}}</button><button v-on:click="switchMode()">{{modeBtnText}}</button>
                        </div>
                        <div style="clear:both;"></div>
                    </nav>
                </div>
            </div>
        `,
        data: function () {
            return {
                productName: '',
                tlaName: '',
                showLoading: true,
                loadingMessage: 'Getting list of tests for this TLA...',
                mode: 'production',
                modeBtnText: 'Switch to Individual Test Mode',
                nextTestText: 'Start',
                autoAdvanceBtnText: 'Turn Off Auto Advance',
                showDisplayImgLarge: false,
                showAuto: true
            }
        },
        mounted: function() {
            var self = this;
            let selectedProduct = store.get('selectedProduct');
            let selectedTLA = store.get('selectedTLA');
            self.tlaName = selectedTLA.tlaName;
            self.productName = selectedProduct.productName;
            window.app.retryVisible = false;
            window.app.passFailVisible = false;
            window.app.showDisplayImg = false;
            window.app.showResultText = false;
            window.app.showStateIndicator = false;
            window.app.instructions = '';

            if(!window.app.auto) {
                self.autoAdvanceBtnText = 'Turn On Auto Advance';
            }

            aniShowLoading().then(() => {
                //Get list of tests from server
                this.connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);
                this.connectionInst.postToServer({
                    'mode': 'getTests',
                    'tla_id': selectedTLA.tlaID
                }, 'tests/').then((response) => {
                    window.app.tests = response.data;

                    //Load test set
                    var imported = document.createElement('script');
                    imported.src = CFG_DATA_SERVICE_URL + 'tests/load-tests.php?tla_id=' + selectedTLA.tlaID;
                    document.head.appendChild(imported);

                    aniHideLoading().then(() => {
                        self.showLoading = false;
                        self.resetView();
                        self.$nextTick(function() {
                            window.app.initVcselCamera(
                                document.getElementById('vcselCameraVideo'),
                                document.getElementById('vcselCameraOverlay')
                            );
                        });
                        /*if(window.app.auto) {
                            setTimeout(() => {
                                runTest(window.app.tests[0].test_id);
                            }, 100);
                        }*/
                        
                    }, () => { });
                }, () => {
                    //Server communications error
                    window.app.errorMessage = "Cannot communicate with server address: " + CFG_DATA_SERVICE_URL + 'tests/';
                    window.app.showError = true;
                    aniShowError().then(() => { }, () => { });
                });
            }, () => { });
        },
        methods: {
            //------------------------------------------------------------------------------------------------------
            hideLargeImg: function() {
                this.showDisplayImgLarge = false;
            },
            //------------------------------------------------------------------------------------------------------
            showLargeImg: function() {
                this.showDisplayImgLarge = true;
            },
            //------------------------------------------------------------------------------------------------------
            switchMode: function() {
                var self = this;
                if(self.mode == 'production') {
                    self.mode = 'singleTest';
                    window.app.dbTestID = 0;
                    window.app.auto = false;
                    self.showAuto = false;
                    self.resetView();
                    window.app.nextTestVisible = false;
                    self.modeBtnText = 'Switch to Production Mode';
                } else {
                    self.mode = 'production';
                    window.app.auto = true;
                    self.showAuto = true;
                    self.resetView();
                    window.app.nextTestVisible = true;
                    self.modeBtnText = 'Switch to Individual Test Mode';
                }
                
            },
            //------------------------------------------------------------------------------------------------------
            switchAuto: function() {
                var self = this;
                if(window.app.auto == true) {
                    window.app.auto = false;
                    self.autoAdvanceBtnText = 'Turn On Auto Advance';
                } else {
                    window.app.auto = true;
                    self.autoAdvanceBtnText = 'Turn Off Auto Advance';
                }
            },
            //------------------------------------------------------------------------------------------------------
            activateTest: function(test_id) {
                if(this.mode!='production') {
                    window.app.dbTestID = 0;
                    window.app.retryVisible = false;
                    window.app.passFailVisible = false;
                    window.app.showDisplayImg = false;
                    window.app.showResultText = false;
                    window.app.showStateIndicator = false;
                    window.app.nextTestVisible = false;
                    for(var i = 0; i < window.app.tests.length; i++) {
                        if(window.app.tests[i].test_id == test_id) {
                            window.app.tests[i].is_current = "1";
                            window.app.instructions = window.app.tests[i].instructions;
                        } else {
                            window.app.tests[i].is_current = "0";
                        }
                    }
                    window.app.armVcselCamera();
                    setTimeout(() => {
                        runTest(test_id);
                    }, 100);
                }
            },
            //------------------------------------------------------------------------------------------------------
            closeErrorPrompt: function() {
                var self = this;
                aniHideErrorPrompt().then(() => {
                    window.app.showErrorPrompt = false;
                }, () => { });
            },
            //------------------------------------------------------------------------------------------------------
            nextTest: function(testIndex) {
                window.app.retryVisible = false;
                window.app.passFailVisible = false;
                window.app.showDisplayImg = false;
                window.app.showResultText = false;
                window.app.showStateIndicator = false;
                window.app.nextTestVisible = false;

                if(this.nextTestText == 'Start') {
                    window.app.startTime = Date.now();
                }

                this.nextTestText = 'Next Test';
                window.app.instructions = window.app.tests[window.app.testIndex].instructions;
                window.app.armVcselCamera();
                runTest(window.app.tests[window.app.testIndex].test_id);
            },
            //------------------------------------------------------------------------------------------------------
            failTest: function() {
                window.app.failTest();
            },
            //------------------------------------------------------------------------------------------------------
            passTest: function() {
                window.app.passTest();
            },
            //------------------------------------------------------------------------------------------------------
            userFailTest: function() {
                window.app.userFailTest();
            },
            //------------------------------------------------------------------------------------------------------
            userPassTest: function() {
                window.app.userPassTest();
            },
            //------------------------------------------------------------------------------------------------------
            retryTest: function() {
                window.app.retryTest();
            },
            //------------------------------------------------------------------------------------------------------
            formatCameraValue: function(value) {
                return Number(value || 0).toFixed(2);
            },
            //------------------------------------------------------------------------------------------------------
            resetView: function() {
                var self = this;
                window.app.retryVisible = false;
                window.app.passFailVisible = false;
                window.app.showDisplayImg = false;
                window.app.showResultText = false;
                window.app.showStateIndicator = false;
                window.app.instructions = window.app.tests[0]['instructions'];
                window.app.testIndex = 0;
                self.nextTestText = 'Start';
                window.app.nextTestVisible = true;
                for(var i = 0; i < window.app.tests.length; i++){
                    window.app.tests[i].is_current = "0";
                    window.app.tests[i].passed_test = "false";
                }
                window.app.tests[0].is_current = "1";
            },
            //------------------------------------------------------------------------------------------------------
        }
    });
});
