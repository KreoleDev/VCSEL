// This file is required by the index.html file and will
// be executed in the renderer process for that window.
// All of the Node.js APIs are available in this process.
const {electron} = require('electron');
const { ipcRenderer } =require('electron');


var FormData = require('form-data');


/*
var serialPort = require('serialport');


//List serial ports
serialPort.list(function(err, ports) {
    console.log(ports);
});
*/

window.addEventListener('load', function() {
    
    window.app = new Vue({
        router,
        store,
        electron,
        data: {
            counter: 0,
            displayImg: '',
            showDisplayImg: false,
            auto: true,
            showError: false,
            errorMessage: '',
            showErrorPrompt: false,
            errorPromptMessage: '',
            instructions: '',
            showStateIndicator: false,
            stateIndicatorMessage: '',
            tests: [],
            scanner: '',
            passFailVisible: false,
            passFailState: 3,
            retryVisible: false,
            nextTestVisible: false,
            testIndex: 0,
            showResultText: false,
            resultText: '',
            resultState: 'fail',
            unitSerialNumber: '',
            testerName: '',
            startTime: 0,
            dbTestID: 0,
            allowBack: true,
            isAuthenticated: false,
            currentUser: '',
            currentUserId: '',
            vcselCamera: null,
            vcselCameraStatus: 'Camera idle',
            vcselCameraBrightness: 0,
            vcselCameraArea: 0,
            vcselCameraReady: false,
            vcselCameraSavedMessage: '',
            vcselCameraSavedMessageTimer: null
        },
        mounted: function() {
            var self = this;
            try {
                self.scanner = new scanner6100();
            } catch (err) {
                console.warn('6100 scanner disabled:', err);
                self.scanner = null;
            }
            self.rs232Device = new rs232Device();
            self.genericUSBDevice = new genericUSBDevice();
            self.isAuthenticated = sessionStorage.getItem('pertechAuthenticated') === '1';
            self.currentUser = sessionStorage.getItem('pertechUsername') || '';
            self.currentUserId = sessionStorage.getItem('pertechUserId') || '';

            ipcRenderer.on('navigate', (e, routePath) => {
                router.push(routePath)
            });

            document.addEventListener('click', function(event) {
                var clickedText = event.target && event.target.innerText ? event.target.innerText.trim().toLowerCase() : '';
                if(clickedText.indexOf('test another vcsel') >= 0) {
                    setTimeout(function() {
                        self.armVcselCamera();
                    }, 100);
                }
            });

            //Load from config
            var savedDataServiceURL = localStorage.getItem("cfgDataServiceURL");
            if (
                savedDataServiceURL === null ||
                (typeof CFG_LEGACY_DATA_SERVICE_URLS !== 'undefined' && CFG_LEGACY_DATA_SERVICE_URLS.indexOf(savedDataServiceURL) >= 0)
            ) {
                localStorage.setItem("cfgDataServiceURL", CFG_DATA_SERVICE_URL);
            } else {
                CFG_DATA_SERVICE_URL = savedDataServiceURL;
            }
        },
        watch: {
            resultText: function(value) {
                this.captureVcselCameraResult(value);
            },
            showResultText: function(value) {
                if(value) {
                    this.captureVcselCameraResult(this.resultText);
                }
            }
        },
        methods: {
            //------------------------------------------------------------------------------------------------------
            logout: function() {
                sessionStorage.removeItem('pertechAuthenticated');
                sessionStorage.removeItem('pertechUsername');
                sessionStorage.removeItem('pertechDisplayName');
                sessionStorage.removeItem('pertechUserId');
                this.isAuthenticated = false;
                this.currentUser = '';
                this.currentUserId = '';
                this.clearVcselCameraSavedMessage();
                if(this.vcselCamera) {
                    this.vcselCamera.resetSavedSerial();
                    this.vcselCamera.disarm();
                }
                this.tests = [];
                this.testerName = '';
                this.showError = false;
                this.showErrorPrompt = false;
                this.showStateIndicator = false;
                this.passFailVisible = false;
                this.retryVisible = false;
                this.nextTestVisible = false;
                this.$router.push('/login');
            },
            //------------------------------------------------------------------------------------------------------
            //------------------------------------------------------------------------------------------------------
            failTest: function() {
                window.app.passFailVisible = false;
                window.app.retryVisible = true;
            },
            //------------------------------------------------------------------------------------------------------
            passTest: function() {
                window.app.passFailVisible = false;
                this.captureVcselCameraResult(window.app.resultText);
                window.app.tests[window.app.testIndex].passed_test = 'true';
                if(window.app.testIndex + 1 < window.app.tests.length) {
                    window.app.tests[window.app.testIndex].is_current = "0";
                    window.app.testIndex++;
                    window.app.tests[window.app.testIndex].is_current = "1";

                    if(window.app.auto) {
                        this.retryTest();
                    } else {
                        window.app.nextTestVisible = true;
                    }
                }
            },
            //------------------------------------------------------------------------------------------------------
            userFailTest: function() {
                window.app.passFailVisible = false;
                this.passFailState = 0;
            },
            //------------------------------------------------------------------------------------------------------
            userPassTest: function() {
                window.app.passFailVisible = false;
                this.passFailState = 1;
            },
            //------------------------------------------------------------------------------------------------------
            retryTest: function() {
                window.app.showResultText = false;
                window.app.showDisplayImg = false;
                window.app.showError = false;
                window.app.showErrorPrompt = false;
                window.app.showStateIndicator = false;
                window.app.passFailVisible = false;
                window.app.retryVisible = false;
                window.app.nextTestVisible = false;
                document.getElementById("altInstructions").innerHTML = '';

                window.app.instructions = window.app.tests[window.app.testIndex].instructions;
                this.armVcselCamera();
                setTimeout(() => {
                    runTest(window.app.tests[window.app.testIndex].test_id);
                }, 100);
            },
            //------------------------------------------------------------------------------------------------------
            initVcselCamera: function(videoElement, overlayCanvas) {
                var self = this;

                if(!window.VcselCameraAnalyzer || !videoElement || !overlayCanvas) {
                    return;
                }

                overlayCanvas.width = 640;
                overlayCanvas.height = 480;

                if(!self.vcselCamera) {
                    self.vcselCamera = new VcselCameraAnalyzer({
                        onStatus: function(status) {
                            self.vcselCameraStatus = status;
                        },
                        onSaved: function(serial) {
                            self.setVcselCameraSavedMessage('Saved Serial ' + serial);
                        },
                        onMeasurement: function(measurement) {
                            self.vcselCameraBrightness = measurement.brightness;
                            self.vcselCameraArea = measurement.area;
                            self.vcselCameraReady = measurement.ready;
                            if(measurement.ready && self.showResultText) {
                                self.captureVcselCameraResult(self.resultText);
                            }
                        },
                        onSaveMeasurement: function(measurement) {
                            self.saveCameraTestingMeasurement(measurement);
                        }
                    });
                }

                self.vcselCamera.setElements(videoElement, overlayCanvas);
                self.vcselCamera.start().catch(function(err) {
                    console.warn('VCSEL camera failed to start:', err);
                });
            },
            //------------------------------------------------------------------------------------------------------
            armVcselCamera: function() {
                this.clearVcselCameraSavedMessage();
                if(this.vcselCamera) {
                    this.vcselCamera.arm();
                }
            },
            //------------------------------------------------------------------------------------------------------
            captureVcselCameraResult: function(resultText) {
                if(this.vcselCamera) {
                    this.vcselCamera.captureSerialResult(resultText);
                }
            },
            //------------------------------------------------------------------------------------------------------
            saveCameraTestingMeasurement: function(measurement) {
                var self = this;
                var connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);

                connectionInst.postToServer({
                    mode: 'saveCameraTesting',
                    timestamp: measurement.timestamp,
                    brightness: measurement.brightness,
                    area: measurement.area,
                    lot_number: measurement.lot_number || 'LOT0000',
                    serial_number: measurement.serial_number,
                    acquired: measurement.acquired || 'Auto',
                    tester_name: self.testerName,
                    logged_in_user_id: self.currentUserId,
                    logged_in_username: self.currentUser
                }, 'camera-testing/').then(function(response) {
                    if(response && response.success) {
                        self.setVcselCameraSavedMessage('Saved Serial ' + measurement.serial_number + ' to dashboard');
                    } else {
                        self.vcselCameraStatus = response && response.error ? response.error : 'Camera data save failed';
                    }
                }, function() {
                    self.vcselCameraStatus = 'Cannot save camera data to server';
                });
            },
            //------------------------------------------------------------------------------------------------------
            setVcselCameraSavedMessage: function(message) {
                var self = this;
                self.vcselCameraSavedMessage = message;
                if(self.vcselCameraSavedMessageTimer) {
                    clearTimeout(self.vcselCameraSavedMessageTimer);
                }
                self.vcselCameraSavedMessageTimer = setTimeout(function() {
                    self.vcselCameraSavedMessage = '';
                    self.vcselCameraSavedMessageTimer = null;
                }, 3000);
            },
            //------------------------------------------------------------------------------------------------------
            clearVcselCameraSavedMessage: function() {
                this.vcselCameraSavedMessage = '';
                if(this.vcselCameraSavedMessageTimer) {
                    clearTimeout(this.vcselCameraSavedMessageTimer);
                    this.vcselCameraSavedMessageTimer = null;
                }
            },

            //------------------------------------------------------------------------------------------------------
        }
    }).$mount('#app');
});
