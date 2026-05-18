'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

let serialPortNativeAvailable = true;
let serialPort;

try {
    serialPort = require('serialport');
} catch (err) {
    serialPortNativeAvailable = false;
    console.warn('Serial port native module disabled:', err.message);
    serialPort = function () {
        throw new Error('Serial port native module is not installed on this Mac dev build');
    };
    serialPort.list = function(callback) {
        callback(null, []);
    };
}
let serialPortInstance;
let serialReceivedData = [];
let serialPortConnected = false;
let lastComPort;
let lastBaudRate;
let lastDataBits;
let lastStopBits;
let lastParity;
let lastRtscts;
let lastXon;
let lastXoff;

function sendSerialByteIndex(byteArray, byteIndex, attemptTimeInTenthsSeconds) {
    //Check status of lines first
    return new Promise(function (resolve, reject) {
        //Verify data is still pending to send
        if(byteIndex < byteArray.length) {

            let modemBits;
            serialPortInstance.get((error,modemBits)=>{
                if(error) {
                    console.log('Attempting reconnect: ' + error);
                    window.app.rs232Device.connect(lastComPort, lastBaudRate, lastDataBits, lastStopBits, lastParity, lastRtscts, lastXon, lastXoff).then(() => {
                        console.log('Reconnected');
                        sendSerialByteIndex(byteArray, byteIndex, attemptTimeInTenthsSeconds - 1).then(() => {
                            resolve();
                        }).catch((err) =>{
                            reject(err);
                        });
                    }, (err) => {
                        reject(err);
                    });
                } else {
                    if(modemBits.cts == false || modemBits.dsr == false) {
                        console.log('Serial Busy: cts = ' + modemBits.cts + ' dsr = ' + modemBits.dsr);
                        if(attemptTimeInTenthsSeconds > 0) {
                            setTimeout(() => {
                                sendSerialByteIndex(byteArray, byteIndex, attemptTimeInTenthsSeconds - 1).then(() => {
                                    resolve();
                                }).catch((err) =>{
                                    reject(err);
                                });
                            }, 100);
                        } else {
                            reject('Timeout');
                        }
                    } else {
                        serialPortInstance.write([byteArray[byteIndex]],'hex',()=>{
                            sendSerialByteIndex(byteArray, byteIndex + 1, attemptTimeInTenthsSeconds).then(() => {
                                resolve();
                            }).catch((err) =>{
                                reject(err);
                            });
                        });
                    }
                }
            });

        } else {
            resolve();
        }
    });
}

function sendSerialByteIndexIgnoreDSR(byteArray, byteIndex, attemptTimeInTenthsSeconds) {
    //Check status of lines first
    return new Promise(function (resolve, reject) {
        //Verify data is still pending to send
        if(byteIndex < byteArray.length) {

            let modemBits;
            serialPortInstance.get((error,modemBits)=>{
                if(error) {
                    console.log('Attempting reconnect: ' + error);
                    window.app.rs232Device.connect(lastComPort, lastBaudRate, lastDataBits, lastStopBits, lastParity, lastRtscts, lastXon, lastXoff).then(() => {
                        console.log('Reconnected');
                        sendSerialByteIndexIgnoreDSR(byteArray, byteIndex, attemptTimeInTenthsSeconds - 1).then(() => {
                            resolve();
                        }).catch((err) =>{
                            reject(err);
                        });
                    }, (err) => {
                        reject(err);
                    });
                } else {
                    if(modemBits.cts == false) {
                        console.log('Serial Busy: cts = ' + modemBits.cts + ' dsr = ' + modemBits.dsr);
                        if(attemptTimeInTenthsSeconds > 0) {
                            setTimeout(() => {
                                sendSerialByteIndexIgnoreDSR(byteArray, byteIndex, attemptTimeInTenthsSeconds - 1).then(() => {
                                    resolve();
                                }).catch((err) =>{
                                    reject(err);
                                });
                            }, 100);
                        } else {
                            reject('Timeout');
                        }
                    } else {
                        serialPortInstance.write(byteArray,'hex',()=>{
                            resolve();
                        });
                    }
                }
            });

        } else {
            resolve();
        }
    });
}

var rs232Device = function () {
    function rs232Device() {
        _classCallCheck(this, rs232Device);
    }
    //------------------------------------------------------------
    _createClass(rs232Device, [
    
    {
        //------------------------------------------------------------
        key: 'findDevices',
        value: function findDevices() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //window.app.stateIndicatorMessage= 'Finding connected serial devices';
                //window.app.showStateIndicator = true;
                setTimeout(() => {
                    serialPort.list(function(err, ports) {
                        if(err) {
                            reject(err);
                        } else {
                            //window.app.showStateIndicator = false;
                            resolve(ports);
                        }
                        
                    });
                },20);
            });
        }
        //------------------------------------------------------------
    }, {
        //------------------------------------------------------------
        key: 'connect',
        value: function connect(comPort, baudRate, dataBits, stopBits, parity, rtscts, xon, xoff) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                if(!serialPortNativeAvailable) {
                    reject('Serial port native module is not installed on this Mac dev build');
                    return;
                }
                lastComPort = comPort;
                lastBaudRate = baudRate;
                lastDataBits = dataBits;
                lastStopBits = stopBits;
                lastParity = parity;
                lastRtscts = rtscts;
                lastXon = xon;
                lastXoff = xoff;

                /*console.log('Com: ' + comPort);
                console.log('baudRate: ' + baudRate);
                console.log('dataBits: ' + dataBits);
                console.log('stopBits: ' + stopBits);
                console.log('parity: ' + parity);
                console.log('rtscts: ' + rtscts);
                console.log('xon: ' + xon);
                console.log('xoff: ' + xoff);*/

                //window.app.stateIndicatorMessage= 'Connecting to device';
                //window.app.showStateIndicator = true;
                serialReceivedData.length = 0; //reset received data
                setTimeout(() => {
                    //Check if already connected
                    if(serialPortConnected) {
                        //Already connected, disconnect first
                        serialPortInstance.close(function(err) {
                            if(err) {
                                console.log('Error on serial close: ' + err);
                            }

                            //window.app.showStateIndicator = false;
                            serialPortConnected = false;
                            serialPortInstance = new serialPort(comPort, {
                                baudRate: parseInt(baudRate),
                                dataBits: parseInt(dataBits),
                                stopBits: parseInt(stopBits),
                                parity: parity,
                                rtscts: rtscts,
                                xon: xon,
                                xoff: xoff
                            }, (err) => {
                                reject(err);
                            });

                            serialPortInstance.on('data',(data)=>{
                                //console.log(data);
                                for(var i=0; i<Buffer.byteLength(data); i++){
                                    serialReceivedData.push(data[i]);
                                }
                            });

                            serialPortInstance.on('open',(data) => {
                                //window.app.showStateIndicator = false;
                                serialPortConnected = true;
                                
                                console.log('connection made');
                                resolve();
                                
                            }, (err) => {
                                console.log('on open err ' + err);
                                reject(err);
                            });
                            
                        });
                    } else {
                        //Not yet connected
                        serialPortInstance = new serialPort(comPort, {
                            baudRate: parseInt(baudRate),
                            dataBits: parseInt(dataBits),
                            stopBits: parseInt(stopBits),
                            parity: parity,
                            rtscts: rtscts,
                            xon: xon,
                            xoff: xoff
                        }, (err) => {
                            reject(err);
                        });

                        serialPortInstance.on('data',(data)=>{
                            //console.log(data);
                            for(var i=0; i<Buffer.byteLength(data); i++){
                                serialReceivedData.push(data[i]);
                            }
                        });

                        serialPortInstance.on('open',(data) => {
                            //window.app.showStateIndicator = false;
                            serialPortConnected = true;
                            
                                console.log('connection made');
                                resolve();
                            
                        }, (err) => {
                            reject(err);
                        });
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
                //window.app.stateIndicatorMessage= 'Disconnecting from device';
                //window.app.showStateIndicator = true;
                setTimeout(() => {
                    serialPortInstance.close(function(err) {
                        if(err) {
                            reject(err);
                        } else {
                            //window.app.showStateIndicator = false;
                            serialPortConnected = false;
                            resolve();
                        }
                        
                    });
                },20);
            });
        }
        //------------------------------------------------------------
    }, {
        //------------------------------------------------------------
        key: 'sendBytes',
        value: function sendBytes(byteArray, attemptTimeInTenthsSeconds) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //window.app.stateIndicatorMessage= 'Sending Bytes';
                //window.app.showStateIndicator = false;
                serialReceivedData.length = 0; //reset received data
                setTimeout(() => {
                    sendSerialByteIndex(byteArray, 0, attemptTimeInTenthsSeconds).then(() => {
                        //window.app.showStateIndicator = false;
                        resolve();
                    }).catch((err) => {
                        //window.app.showStateIndicator = false;
                        reject(err);
                    });
                },20);
            });
        }
        //------------------------------------------------------------
    }, {
        //------------------------------------------------------------
        key: 'sendBytesIgnoreDSR',
        value: function sendBytesIgnoreDSR(byteArray, attemptTimeInTenthsSeconds) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //window.app.stateIndicatorMessage= 'Sending Bytes';
                //window.app.showStateIndicator = false;
                serialReceivedData.length = 0; //reset received data
                setTimeout(() => {
                    sendSerialByteIndexIgnoreDSR(byteArray, 0, attemptTimeInTenthsSeconds).then(() => {
                        //window.app.showStateIndicator = false;
                        resolve();
                    }).catch((err) => {
                        //window.app.showStateIndicator = false;
                        reject(err);
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
                var countDown = 30; //3 seconds
                var waitForData = setInterval(() => {
                    if(serialReceivedData.length >= byteCountExpected) {
                        clearInterval(waitForData);
                        //window.app.showStateIndicator = false;
                        resolve(serialReceivedData);
                    }

                    countDown--;
                    if(countDown < 1) {
                        clearInterval(waitForData);
                        //window.app.showStateIndicator = false;
                        reject();
                    }
                },100);
            });
        }
        //------------------------------------------------------------
    }

    ]);

    return rs232Device;
}();
