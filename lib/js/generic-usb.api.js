'use strict';

function usbOpenProcess(interfaceNum, outEndpoint, inEndpoint) {
    usbDevice.open();
    usbConnected = true;

    usbInterface = usbDevice.interface(interfaceNum);
    
    driverAttached = false;
    if (usbInterface.isKernelDriverActive()) {
        console.log('detach from kernel');
        driverAttached = true;
        usbInterface.detachKernelDriver();
    }

    usbInterface.claim();
    usbBulkOut = usbInterface.endpoint(outEndpoint);
    usbBulkIn = usbInterface.endpoint(inEndpoint);

    usbReceivedData.length = 0;
    usbBulkIn.startPoll(3,64);
    console.log('Polling started');
    usbBulkIn.on('data',(data)=> {
        if(Buffer.byteLength(data) > 0) {
            console.log('data received = ' + Buffer.byteLength(data));
            for(var i=0; i<Buffer.byteLength(data); i++){
                usbReceivedData.push(data[i]);
            }
        }
    });

    usbBulkIn.on('error',(error)=>{
        console.log('USB: ' + error);

        //Perform disconnect
        //window.app.genericUSBDevice.disconnect().then(() => {
        usbInterface.release(true,(err)=>{
            /*if (driverAttached) {
                console.log('attach to kernel');
                driverAttached = false;
                usbInterface.attachKernelDriver()
            }*/

            usbDevice.close();
            usbConnected = false;

            //Attempt reconnect
            //console.log('Attempting Reconnect..');
            //window.app.genericUSBDevice.connect(lastVid, lastPid, lastInterfaceNum, lastOutEndpoint, lastInEndpoint);
        });
        //});
    });

    //window.app.showStateIndicator = false;
};

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

let usbNativeAvailable = true;
let usb;

try {
    usb = require('usb');
} catch (err) {
    usbNativeAvailable = false;
    console.warn('USB native module disabled:', err.message);
    usb = {
        on: function () {},
        findByIds: function () {
            return undefined;
        }
    };
}
let usbConnected = false;
let usbDevice;
let usbInterface;
let usbBulkOut;
let usbBulkIn;
let driverAttached = false;
let usbReceivedData = [];



usb.on('detach',(device) => {
    if(usbConnected) {
        console.log('detach operation');
        usbDevice.close();
        usbConnected = false;
    }
});

var genericUSBDevice = function () {
    function genericUSBDevice() {
        _classCallCheck(this, genericUSBDevice);
    }
    //------------------------------------------------------------
    _createClass(genericUSBDevice, [
    
    {
        //------------------------------------------------------------
        key: 'connect',
        value: function connect(vid, pid, interfaceNum, outEndpoint, inEndpoint) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                if(!usbNativeAvailable) {
                    reject('USB native module is not installed in this build');
                    return;
                }
                //window.app.stateIndicatorMessage= 'Connecting to USB device';
                //window.app.showStateIndicator = true;
                setTimeout(() => {
                    //Determine if connected first
                    if(usbConnected) {
                        console.log('Disconnect first');
                        _this.disconnect().then(() => {
                            usbDevice = usb.findByIds(vid, pid);
                            if(usbDevice !== undefined){
                                usbOpenProcess(interfaceNum, outEndpoint, inEndpoint);
                                resolve();
                            } else {
                                reject('Could not find device on USB');
                            }
                        }, (err) => {
                            reject(err);
                        });
                    } else {
                        usbDevice = usb.findByIds(vid, pid);
                        if(usbDevice !== undefined){
                            usbOpenProcess(interfaceNum, outEndpoint, inEndpoint);
                            resolve();
                        } else {
                            reject('Could not find device on USB');
                        }
                    }
                },20);
            });
        }
        //------------------------------------------------------------
    }, {
        //------------------------------------------------------------
        key: 'disconnect',
        value: function disconnect() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //window.app.stateIndicatorMessage= 'Disconnecting from USB device';
                //window.app.showStateIndicator = true;
                setTimeout(() => {
                    if(usbConnected) {
                        usbBulkIn.stopPoll(()=>{
                            usbInterface.release(true,(err)=>{
                                if(err !== undefined){
                                    //window.app.showStateIndicator = false;
                                    reject(err);
                                } else {
                                    /*if (driverAttached) {
                                        console.log('attach to kernel');
                                        driverAttached = false;
                                        usbInterface.attachKernelDriver()
                                    }*/

                                    usbDevice.close();
                                    //window.app.showStateIndicator = false;
                                    usbConnected = false;
                                    resolve();
                                }
                            });
                        });
                    } else {
                        //Not connected
                        //window.app.showStateIndicator = false;
                        reject('Not Connected');
                    }  
                },20);
            });
        }
        //------------------------------------------------------------
    }, {
        //------------------------------------------------------------
        key: 'sendBytes',
        value: function sendBytes(byteArray) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //console.log('Sending bytes: ' + byteArray);
                //window.app.stateIndicatorMessage= 'Sending Bytes';
                //window.app.showStateIndicator = false;
                usbReceivedData.length = 0; //reset received data
                setTimeout(() => {
                    usbBulkOut.transfer(byteArray,(err)=>{
                        //window.app.showStateIndicator = false;
                        if(err !== undefined){
                            console.log('Send error: ' + err);
                            reject(err);
                        } else {
                            resolve();
                        }
                    });
                },20);
            });
        }
        //------------------------------------------------------------
    }, {
        //------------------------------------------------------------
        key: 'getBytes',
        value: function getBytes(byteCountExpected) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //window.app.stateIndicatorMessage= 'Getting Bytes';
                //window.app.showStateIndicator = false;
                //console.log('Getting bytes count: ' + byteCountExpected);
                var countDown = 30; //3 seconds
                var waitForData = setInterval(() => {
                    if(usbReceivedData.length >= byteCountExpected) {
                        clearInterval(waitForData);
                        //window.app.showStateIndicator = false;
                        resolve(usbReceivedData);
                    }

                    countDown--;
                    if(countDown < 1) {
                        clearInterval(waitForData);
                        //window.app.showStateIndicator = false;
                        reject('Invalid Data');
                    }
                },100);
            });
        }
        //------------------------------------------------------------
    }


    ]);

    return genericUSBDevice;
}();
