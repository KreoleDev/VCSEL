'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

const fs = require('fs');
let scannerNativeAvailable = true;
let ref;
let ffi;
let Struct;

try {
    ref = require('ref');
    ffi = require('ffi');
    Struct = require('ref-struct');
} catch (err) {
    scannerNativeAvailable = false;
    console.warn('6100 scanner native module disabled:', err.message);

    ref = {
        refType: function () {
            return function () {};
        }
    };

    Struct = function () {
        return function () {
            this.ref = function () {
                return this;
            };
        };
    };

    ffi = {
        Library: function () {
            return new Proxy({}, {
                get: function () {
                    return function () {
                        return -1;
                    };
                }
            });
        }
    };
}

var statStruct = Struct({
    'ub_uf': 'uint8',
    'ub_id': 'uint8',
    'ub_cc': 'uint8',
    'ub_sens': 'uint8',
    'ub_bx': 'uint8',
    'ub_bs': 'uint8',
    'ub_stat': 'uint8',
    'ub_sstat': 'uint8',
    'uw_cmdno': 'uint16',
    'uw_docid': 'uint16',
    'ul_vlen': 'uint32'
  });
  var statStructPtr = ref.refType(statStruct);


var scannerAPI = ffi.Library('libPISCAN', {
    'PiEZOpenDevice': ['int',[]],
    'PiEZCloseDevice': ['int',[]],
    'PiEZGetStatus': ['int',[statStructPtr]],
    'PiEZControlLEDBlink': ['int',['uint8','uint8']],
    'PiEZSetDeskew': ['int',['uint8']],
    'PiEZPerformScan': ['int',['int', 'int', 'int','int']],
    'PiEZWaitForScanComplete': ['int',[]],
    'PiEZCancelScanWait': ['int',[]],
    'PiEZGetOrientation': ['int',['uint8 *','int']],
    'PiEZGetLastDocument': ['int',['int','int','int','uint32 *','uint8 *']],
    'PiEZGetLastMICR': ['int',['uint32','uint32', 'uint32 *','uint8 *']],
    'PiEZGetPropLastMICRDecodeScheme': ['int',['uint32 *']],
    'PiEZGetPropLastMICRDecodeFont': ['int', ['uint32 *']],
    'PiEZRewind': ['int',[]],
    'PiEZFwdFeedAndStamp': ['int',['uint16']],
    'PiEZEjectDocument': ['int',['uint8']],
    'PiEZAcceleratedRearEject': ['int',[]],
    'PiEZStampAndEject': ['int',['uint16', 'uint8']],
    'PiScanCortexSetSkew': ['int',['uint8', 'uint8']],
    'PiScanXmitRawMICR': ['int', ['uint8','uint32 *','uint8 *']],
    'PiScanResetScanner': ['int',[]],
    'PiScanSoftResetScanner': ['int', []],
    'PiScanGetPropAPIVersion': ['int', ['string']],
    'PiScanXfn': ['int', ['uint8','uint32 *','uint8 *']],
    'PiScanCortexSetSerialNum': ['int',['uint8 *']],
    'PiScanCortexSetUSBDescriptorMode': ['int', ['uint8']],
    'PiScanCortexUpdateFirmware': ['int', ['uint8 *', 'uint8 *', 'uint32']],
    'PiScanCalibrateScannerWhite': ['int',[]],
    'PiScanCortexAdjustUVCutoff': ['int', ['uint8']],
	'PiScanCortexProcessWatermark': ['int', ['uint8']],
    'PiScanCortexSetDoubleFeedDetection': ['int', ['uint8']],
    'PiScanCortexSetDoubleFeedTolerances': ['int', ['uint16', 'uint16', 'uint16', 'uint8', 'uint8']],
    'PiScanCortexGetDoubleFeedTolerances': ['int', ['uint16 *', 'uint16 *', 'uint16 *', 'uint8 *', 'uint8 *']],
    'PiScanCortexGetDoubleFeedSensorScanAvg': ['int', ['uint16 *']],

    //MANUFACTURING
    'PiScanIsCortex': ['uint8', []],
    'PiScanSetOCRAssist': ['int', ['uint8']],
    'PiScanSetCalibrationTable': ['int', ['uint32 *','uint8 *']],
    'PiScanGetCalibrationTable': ['int', ['uint32 *','uint8 *']],
    'PiScanCortexSetScannerHardware': ['int', ['uint8 *']],
    'PiScanCortexGetScannerHardware': ['int', ['uint8 *']],
    'PiScanCortexSetScannerSoftware': ['int', ['uint8 *']],
    'PiScanCortexGetScannerSoftware': ['int', ['uint8 *']],
    'PiScanCortexSetTLA': ['int', ['uint8 *']],
    'PiScanCortexSetProductionSerialNum': ['int', ['uint8 *']],
    'PiScanCortexGetProductionSerialNum': ['int', ['uint8 *']],
    'PiScanCortexResetTallies': ['int', []],
    'PiScanInjectMICRandTIFF': ['int', ['uint32','uint8 *', 'uint32','uint8 *', 'uint8', 'uint32 *','uint8 *', 'uint32 *','uint8 *','uint32 *','uint8 *', 'uint32 *','uint8 *']],
    'PiScanInjectMICRandTIFFforCMC7': ['int', ['uint32','uint8 *', 'uint32','uint8 *', 'uint8', 'uint32 *','uint8 *', 'uint32 *','uint8 *']],
    'PiScanGetFullE13bMICR': ['int', ['uint32 *','uint8 *', 'uint32 *','uint8 *','uint32 *','uint8 *', 'uint32 *','uint8 *']],
    'PiScanGetTIFFfromOCR': ['int', ['uint32 *','uint8 *']]
});

var scannerErrorCodes = [
    "OK",                   //0
    "Jam",                  //1
    "No Buffer",            //2
    "Parameter N/A",        //3
    "Parameter Fail",       //4
    "Waiting",              //5
    "In Progress",          //6
    "Operation Fail",       //7
    "TimeOut",              //8
    "Invalid Buffer",       //9
    "Document Length",      //10
    "No Image",             //11
    "Small Crop",           //12
    "Reset",                //13
    "Motor Time",           //14
    "No MICR",              //15
    "Cover Open",           //16
    "Document Size",        //17
    "Not Implemented",      //18
    "No Document",          //19
    "Skew",                 //20
    "Not Waiting",          //21
    "MICR OverFlow",        //22
    "Low Power",            //23
    "Xmit N/A",             //24
    "Warmup",               //25
    "No Swipe",             //26
    "BMP Error",            //27
    "Too Short",            //28
    "Double Feed",          //29
    "Stacker Error",        //30
    "Not Connected",        //31
    "Not Open",             //32
    "Data Overflow",        //33
    "Small Buffer",         //34
    "Not Compatible",       //35
    "FW Download",          //36
    "Bad Size Buffer",      //37
    "No Acknowledge",       //38
    "Undefined",            //39
    "Undefined",            //40
    "Undefined",            //41
    "Illogical",            //42
    "Undefined",            //43
    "Undefined",            //44
    "Undefined",            //45
    "Undefined",            //46
    "Undefined",            //47
    "Undefined",            //48
    "Undefined",            //49
    "Lockup",               //50
    "System Error",         //51
    "MICR Decode Fwd Err",  //52
    "MICR Decode Rev Err",  //53
    "MICR Sec Lib Err",     //54
    "MICR Decode CMC Err",  //55
    "Thread Err",           //56
    "Data Retrieve Err",    //57
    "Unexpected Data Err",  //58
    "Internal Memory Err",  //59
    "File Error",             //60
    "Output Error",           //61
    "Unexpected Doc In Path"  //62
];

var recBuf = Buffer.alloc(52428800);
var micrBuf = Buffer.alloc(160000);
var recBufMag1 = Buffer.alloc(81);
var recBufMag2 = Buffer.alloc(81);
var recBufOCR = Buffer.alloc(81);
var recBufCombined = Buffer.alloc(81);

var sizedBuf;
var lastRawMICR;

var scanner6100 = function () {
    function scanner6100() {
        _classCallCheck(this, scanner6100);
    }
    //------------------------------------------------------------
    _createClass(scanner6100, [
    
    {
        //------------------------------------------------------------
        key: 'openDevice',
        value: function openDevice() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                if(!scannerNativeAvailable) {
                    reject('6100 scanner native module is not installed in this build');
                    return;
                }
                window.app.stateIndicatorMessage= 'Attempting connection to scanner';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZOpenDevice();
                    if(response == 0) {
                        window.app.showStateIndicator = false;
                        resolve();
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    
    }, {
        //------------------------------------------------------------
        key: 'closeDevice',
        value: function closeDevice() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Closing connection';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZCloseDevice();
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    
    }, {
        //------------------------------------------------------------
        key: 'getStatus',
        value: function getStatus() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                //window.app.stateIndicatorMessage= 'Getting status';
                //window.app.showStateIndicator = true;
                setTimeout(() => {
                    var statusResponse = new statStruct();
                    var response = scannerAPI.PiEZGetStatus(statusResponse.ref());
                    //window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve(statusResponse);
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    
    }, {
        //------------------------------------------------------------
        key: 'rewind',
        value: function rewind() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Rewinding';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZRewind();
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'eject',
        value: function eject(direction) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Ejecting';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZEjectDocument(direction);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {

        
        //------------------------------------------------------------
        key: 'scan',
        value: function scan(color, dpi, scanType, waitTime) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Waiting for document and scan';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZPerformScan(color, dpi, scanType, waitTime);
                    if(response == 0) {
                        console.log('waitforscancomplete');
                        response = scannerAPI.PiEZWaitForScanComplete();
                        console.log('end waitfor');
                    }
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setDeskew',
        value: function setDeskew(tolerance) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting Deskew';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZSetDeskew(tolerance);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getLastDoc',
        value: function getLastDoc(format, docSide, rotation) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Retrieving image';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen = ref.alloc('uint32',52428800);
                    var response = scannerAPI.PiEZGetLastDocument(format, docSide, rotation, recLen, recBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        sizedBuf = recBuf.slice(0,recLen.deref());
                        var extension = 'jpg';
                        if(format == 0 || format == 1 || format == 2 || format == 9){
                            extension = 'tif';
                        } else if(format == 10 || format == 11) {
                            extension = 'bmp';
                        }
                        fs.writeFile('/tmp/scan.' + extension,sizedBuf, 'utf8', (err) => {
                            if(err) {
                                reject(60); //File Error
                            } else {
                                resolve();
                            }
                        });
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getLastImgBuffer',
        value: function getLastImgBuffer() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                resolve(sizedBuf);
            });    
        }
    }, {
        //------------------------------------------------------------
        key: 'getLastMICR',
        value: function getLastMICR(scheme, font) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting MICR';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen = ref.alloc('uint32',52428800);
                    var response = scannerAPI.PiEZGetLastMICR(scheme, font, recLen, recBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        recBuf[recLen.deref()] = 0;
                        var micr = recBuf.slice(0,recLen.deref());
                        resolve(ref.readCString(micr, 0));
                    } else {
                        reject(response); 
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getRawMICR',
        value: function getRawMICR() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting RAW MICR';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen = ref.alloc('uint32',160000);
                    var response = scannerAPI.PiScanXmitRawMICR(1,recLen, micrBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        lastRawMICR = micrBuf.slice(0,recLen.deref());
                        fs.writeFile('/tmp/micr.raw',lastRawMICR, 'utf8', (err) => {
                            if(err) {
                                reject(60); //File Error
                            } else {
                                resolve();
                            }
                        });
                    } else {
                        reject(response); 
                    }
                },20);
            });
        }
    }, {
        
        
        //------------------------------------------------------------
        key: 'isCortex',
        value: function isCortex() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Checking if cortex';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanIsCortex();
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        reject(); //Not cortex
                    } else {
                        resolve(); //Cortex
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getAPIVersion',
        value: function getAPIVersion() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Checking if cortex';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var stringBuffer = Buffer.alloc(32);
                    var response = scannerAPI.PiScanGetPropAPIVersion(stringBuffer);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        var apiVersion = ref.readCString(stringBuffer, 0);
                        resolve(apiVersion); 
                    } else {
                        reject(response); 
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'xfn',
        value: function xfn(diagFn) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Performing XFN';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen = ref.alloc('uint32',52428800);
                    var response = scannerAPI.PiScanXfn(diagFn, recLen, recBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        recBuf[recLen.deref()] = 0;
                        var sizedBuf = recBuf.slice(0,recLen.deref() + 1);
                        resolve(ref.readCString(sizedBuf, 0));
                    } else {
                        reject(response); 
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setOCRAssist',
        value: function setOCRAssist(OCRAssistMode) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting OCR Assist';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanSetOCRAssist(OCRAssistMode);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'updateFirmware',
        value: function updateFirmware(firmwareData, firmwareMD5) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Updating Firmware';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var md5Calc = Buffer.alloc(16);
                    var newIndex = 0;

                    for(var i = 0; i < 32; i+=2) {
                        md5Calc[newIndex] = parseInt("0x" + firmwareMD5.substr(i,2),16);
                        newIndex++;
                    }
                    
                    var arrayBuffer;
                    var fileReader = new FileReader();
                    fileReader.onload = function(event) {
                        arrayBuffer = event.target.result;
                    };
                    fileReader.readAsArrayBuffer(firmwareData);
                    fileReader.onloadend = function(event) {
                        var curBuf = Buffer.from(fileReader.result);
                        var response = scannerAPI.PiScanCortexUpdateFirmware(md5Calc,curBuf,firmwareData.size);
                        window.app.showStateIndicator = false;
                        if(response == 0) {
                            resolve();
                        } else {
                            reject(response); 
                        }
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setHardware',
        value: function setHardware(hardwareConfig) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting Hardware Config';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    
                    var curBuf = Buffer.from(hardwareConfig);
                    var response = scannerAPI.PiScanCortexSetScannerHardware(curBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getHardware',
        value: function getHardware() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting Hardware Config';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var hardwareBuffer = Buffer.alloc(10);
                    var response = scannerAPI.PiScanCortexGetScannerHardware(hardwareBuffer);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve(Array.prototype.slice.call(hardwareBuffer, 0));
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setSoftware',
        value: function setSoftware(softwareConfig) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting Software Config';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    
                    var curBuf = Buffer.from(softwareConfig);
                    var response = scannerAPI.PiScanCortexSetScannerSoftware(curBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getSoftware',
        value: function getSoftware() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting Software Config';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var softwareBuffer = Buffer.alloc(10);
                    var response = scannerAPI.PiScanCortexGetScannerSoftware(softwareBuffer);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve(Array.prototype.slice.call(softwareBuffer, 0));
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setTLA',
        value: function setTLA(tlaValue) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting TLA';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    
                    var curBuf = Buffer.from(tlaValue);
                    var response = scannerAPI.PiScanCortexSetTLA(curBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setUSBDescriptor',
        value: function setUSBDescriptor(usbMode) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting USB Descriptor Mode';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanCortexSetUSBDescriptorMode(usbMode);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setSkew',
        value: function setSkew(skewTolerance, skewReject) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting skew tolerance and reject';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanCortexSetSkew(skewTolerance, skewReject);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setSerialNumber',
        value: function setSerialNumber(barcodeValue) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting serial number';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    
                    var curBuf = Buffer.from(barcodeValue);
                    var response = scannerAPI.PiScanCortexSetSerialNum(curBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setProductionSerialNumber',
        value: function setProductionSerialNumber(barcodeValue) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting production serial number';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    
                    var curBuf = Buffer.from(barcodeValue);
                    var response = scannerAPI.PiScanCortexSetProductionSerialNum(curBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getProductionSerialNumber',
        value: function getProductionSerialNumber() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting production serial number';
                window.app.showStateIndicator = true;
                setTimeout(() => {

                    var response = scannerAPI.PiScanCortexGetProductionSerialNum(recBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        recBuf[10] = 0;
                        var sizedBuf = recBuf.slice(0,11);
                        resolve(ref.readCString(sizedBuf, 0));
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'calibrate',
        value: function calibrate() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Calibrating';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanCalibrateScannerWhite();
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'adjustUVCutoff',
        value: function adjustUVCutoff(UVThreshold) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Adjusting UV Cutoff';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanCortexAdjustUVCutoff(UVThreshold);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'processWatermark',
        value: function processWatermark(processLevel) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Process Watermark';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanCortexProcessWatermark(processLevel);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'reset',
        value: function reset() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Resetting';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanResetScanner();
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'fwdFeedAndStamp',
        value: function fwdFeedAndStamp(steps) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Forward Feeding and Stamping';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiEZFwdFeedAndStamp(steps);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'resetTallies',
        value: function resetTallies() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Resetting Tallies';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var response = scannerAPI.PiScanCortexResetTallies();
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }

                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getCalibrationTable',
        value: function getCalibrationTable() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Retrieving calibration';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen = ref.alloc('uint32',52428800);
                    var response = scannerAPI.PiScanGetCalibrationTable(recLen, recBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        sizedBuf = recBuf.slice(0,recLen.deref());
                        fs.writeFile('/tmp/calibration.raw',sizedBuf, 'utf8', (err) => {
                            if(err) {
                                reject(60); //File Error
                            } else {
                                resolve();
                            }
                        });
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'setCalibrationTable',
        value: function setCalibrationTable(recLen, recBuf) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Setting calibration';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen2 = ref.alloc('uint32',recLen);
                    var arrayBuffer;
                    var fileReader = new FileReader();
                    fileReader.onload = function(event) {
                        arrayBuffer = event.target.result;
                    };
                    fileReader.readAsArrayBuffer(recBuf);
                    fileReader.onloadend = function(event) {
                        var curBuf = Buffer.from(fileReader.result);
                        var response = scannerAPI.PiScanSetCalibrationTable(recLen2, curBuf);
                        window.app.showStateIndicator = false;
                        if(response == 0) {
                            resolve();
                        } else {
                            reject(response);
                        }
                    }

                    
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getTIFFfromOCR',
        value: function getTIFFfromOCR() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Retrieving image';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLen = ref.alloc('uint32',52428800);
                    var response = scannerAPI.PiScanGetTIFFfromOCR(recLen, recBuf);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        sizedBuf = recBuf.slice(0,recLen.deref());
                        fs.writeFile('/tmp/scan_ocr.tif',sizedBuf, 'utf8', (err) => {
                            if(err) {
                                reject(60); //File Error
                            } else {
                                resolve();
                            }
                        });
                    } else {
                        reject(response);
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'getFullE13bMICR',
        value: function getFullE13bMICR() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting MICR';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLenMag1 = ref.alloc('uint32',81);
                    var recLenMag2 = ref.alloc('uint32',81);
                    var recLenOCR = ref.alloc('uint32',81);
                    var recLenCombined = ref.alloc('uint32',81);
                    var response = scannerAPI.PiScanGetFullE13bMICR(recLenMag1, recBufMag1, recLenMag2, recBufMag2, recLenOCR, recBufOCR, recLenCombined, recBufCombined);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        recBufMag1[recLenMag1.deref()] = 0;
                        var micrMag1 = recBufMag1.slice(0,recLenMag1.deref());
                        recBufMag2[recLenMag2.deref()] = 0;
                        var micrMag2 = recBufMag2.slice(0,recLenMag2.deref());
                        recBufOCR[recLenOCR.deref()] = 0;
                        var micrOCR = recBufOCR.slice(0,recLenOCR.deref());
                        recBufCombined[recLenCombined.deref()] = 0;
                        var micrCombined = recBufCombined.slice(0,recLenCombined.deref());

                        resolve({
                            'micrMag1': ref.readCString(micrMag1, 0),
                            'micrMag2': ref.readCString(micrMag2, 0),
                            'micrOCR': ref.readCString(micrOCR, 0),
                            'micrCombined': ref.readCString(micrCombined, 0)
                        });
                    } else {
                        reject(response); 
                    }
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'injectMICRandTIFF',
        value: function injectMICRandTIFF(rawMICR, tiffImg, scannerType) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting MICR';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLenMag1 = ref.alloc('uint32',81);
                    var recLenMag2 = ref.alloc('uint32',81);
                    var recLenOCR = ref.alloc('uint32',81);
                    var recLenCombined = ref.alloc('uint32',81);

                    //MICR
                    var arrayBuffer;
                    var fileReader = new FileReader();
                    fileReader.onload = function(event) {
                        arrayBuffer = event.target.result;
                    };
                    fileReader.readAsArrayBuffer(rawMICR);
                    fileReader.onloadend = function(event) {
                        var micrBuf = Buffer.from(fileReader.result);
                        //Img
                        var arrayBufferImg;
                        var fileReaderImg = new FileReader();
                        fileReaderImg.onload = function(event) {
                            arrayBufferImg = event.target.result;
                        };
                        fileReaderImg.readAsArrayBuffer(tiffImg);
                        fileReaderImg.onloadend = function(event) {
                            var imgBuf = Buffer.from(fileReaderImg.result);
                            //var recLenMICR = ref.alloc('uint32',rawMICR.size);
                            //var recLenImg = ref.alloc('uint32',tiffImg.size);
                            var response = scannerAPI.PiScanInjectMICRandTIFF(rawMICR.size, micrBuf, tiffImg.size, imgBuf, scannerType, recLenMag1, recBufMag1, recLenMag2, recBufMag2, recLenOCR, recBufOCR, recLenCombined, recBufCombined);
                            window.app.showStateIndicator = false;
                            if(response == 0) {
                                recBufMag1[recLenMag1.deref()] = 0;
                                var micrMag1 = recBufMag1.slice(0,recLenMag1.deref());
                                recBufMag2[recLenMag2.deref()] = 0;
                                var micrMag2 = recBufMag2.slice(0,recLenMag2.deref());
                                recBufOCR[recLenOCR.deref()] = 0;
                                var micrOCR = recBufOCR.slice(0,recLenOCR.deref());
                                recBufCombined[recLenCombined.deref()] = 0;
                                var micrCombined = recBufCombined.slice(0,recLenCombined.deref());

                                resolve({
                                    'micrMag1': ref.readCString(micrMag1, 0),
                                    'micrMag2': ref.readCString(micrMag2, 0),
                                    'micrOCR': ref.readCString(micrOCR, 0),
                                    'micrCombined': ref.readCString(micrCombined, 0)
                                });
                            } else {
                                reject(response); 
                            }
                        }
                    }
                    
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'injectMICRandTIFFforCMC7',
        value: function injectMICRandTIFFforCMC7(rawMICR, tiffImg, scannerType) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                window.app.stateIndicatorMessage= 'Getting MICR';
                window.app.showStateIndicator = true;
                setTimeout(() => {
                    var recLenMag1 = ref.alloc('uint32',81);
                    var recLenOCR = ref.alloc('uint32',81);

                    //MICR
                    var arrayBuffer;
                    var fileReader = new FileReader();
                    fileReader.onload = function(event) {
                        arrayBuffer = event.target.result;
                    };
                    fileReader.readAsArrayBuffer(rawMICR);
                    fileReader.onloadend = function(event) {
                        var micrBuf = Buffer.from(fileReader.result);
                        //Img
                        var arrayBufferImg;
                        var fileReaderImg = new FileReader();
                        fileReaderImg.onload = function(event) {
                            arrayBufferImg = event.target.result;
                        };
                        fileReaderImg.readAsArrayBuffer(tiffImg);
                        fileReaderImg.onloadend = function(event) {
                            var imgBuf = Buffer.from(fileReaderImg.result);

                            var response = scannerAPI.PiScanInjectMICRandTIFFforCMC7(rawMICR.size, micrBuf, tiffImg.size, imgBuf, scannerType, recLenMag1, recBufMag1, recLenOCR, recBufOCR);
                            window.app.showStateIndicator = false;
                            if(response == 0) {
                                recBufMag1[recLenMag1.deref()] = 0;
                                var micrMag1 = recBufMag1.slice(0,recLenMag1.deref());
                                recBufOCR[recLenOCR.deref()] = 0;
                                var micrOCR = recBufOCR.slice(0,recLenOCR.deref());

                                resolve({
                                    'micrMag1': ref.readCString(micrMag1, 0),
                                    'micrOCR': ref.readCString(micrOCR, 0)
                                });
                            } else {
                                reject(response); 
                            }
                        }
                    }
                    
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'cortexSetDoubleFeedDetection',
        value: function cortexSetDoubleFeedDetection(enableState) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                setTimeout(() => {

                    var response = scannerAPI.PiScanCortexSetDoubleFeedDetection(enableState ? 1 : 0);
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }
                    
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'cortexSetDoubleFeedTolerances',
        value: function cortexSetDoubleFeedTolerances(paperPresenceTrigger, doublePaperPresenceTrigger, doubleFeedDistanceTrigger, reserved1, reserved2) {
            var _this = this;

            return new Promise(function (resolve, reject) {
                setTimeout(() => {

                    var response = scannerAPI.PiScanCortexSetDoubleFeedTolerances(paperPresenceTrigger, doublePaperPresenceTrigger, doubleFeedDistanceTrigger, reserved1, reserved2);
                    if(response == 0) {
                        resolve();
                    } else {
                        reject(response); 
                    }
                    
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'cortexGetDoubleFeedTolerances',
        value: function cortexGetDoubleFeedTolerances() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                setTimeout(() => {

                    var paperPresenceTrigger = ref.alloc('uint16',0);
                    var doublePaperPresenceTrigger = ref.alloc('uint16',0);
                    var doubleFeedDistanceTrigger = ref.alloc('uint16',0);
                    var reserved1 = ref.alloc('uint8',0);
                    var reserved2 = ref.alloc('uint8',0);
                    var response = scannerAPI.PiScanCortexGetDoubleFeedTolerances(paperPresenceTrigger, doublePaperPresenceTrigger, doubleFeedDistanceTrigger, reserved1, reserved2);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve({
                            'paperPresenceTrigger': paperPresenceTrigger.deref(),
                            'doublePaperPresenceTrigger': doublePaperPresenceTrigger.deref(),
                            'doubleFeedDistanceTrigger': doubleFeedDistanceTrigger.deref(),
                            'reserved1': reserved1.deref(),
                            'reserved2': reserved2.deref()
                        });
                    } else {
                        reject(response); 
                    }
                    
                },20);
            });
        }
    }, {
        //------------------------------------------------------------
        key: 'cortexGetDoubleFeedSensorScanAvg',
        value: function cortexGetDoubleFeedSensorScanAvg() {
            var _this = this;

            return new Promise(function (resolve, reject) {
                setTimeout(() => {

                    var avgSensorValue = ref.alloc('uint16',0);
                    var response = scannerAPI.PiScanCortexGetDoubleFeedSensorScanAvg(avgSensorValue);
                    window.app.showStateIndicator = false;
                    if(response == 0) {
                        resolve({
                            'avgSensorValue': avgSensorValue.deref()
                        });
                    } else {
                        reject(response); 
                    }
                    
                },20);
            });
        }
    }
    ]);

    return scanner6100;
}();
