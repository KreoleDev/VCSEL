import * as fs from 'node:fs/promises'

const { getNativeFunction, getBufferPointer } = require('sbffi')
const ref = require('ref-napi')
const StructType = require('ref-struct-di')(ref)

const libPath = '/usr/local/lib/libPISCAN.so'

const statusStruct = StructType({
    ub_uf: ref.types.uint8,
    ub_id: ref.types.uint8,
    ub_cc: ref.types.uint8,
    ub_sens: ref.types.uint8,
    ub_bx: ref.types.uint8,
    ub_bs: ref.types.uint8,
    ub_stat: ref.types.uint8,
    ub_sstat: ref.types.uint8,
    cmdno: ref.types.uint16,
    docid: ref.types.uint16,
    ul_vlen: ref.types.uint32
})
const statusStructPtr = ref.refType(statusStruct)

// EZ commands
const openDevice = getNativeFunction(libPath, 'PiEZOpenDevice', 'int', [])
const closeDevice = getNativeFunction(libPath, 'PiEZCloseDevice', 'int', [])
const getStatus = getNativeFunction(libPath, 'PiEZGetStatus', 'int', ['uint8_t *'])
const controlLEDBlink = getNativeFunction(libPath, 'PiEZControlLEDBlink', 'int', ['uint8_t', 'uint8_t'])
const setDeskew = getNativeFunction(libPath, 'PiEZSetDeskew', 'int', ['uint8_t'])
const performScan = getNativeFunction(libPath, 'PiEZPerformScan', 'int', ['uint8_t', 'uint16_t', 'uint8_t', 'uint16_t'])
const waitForScanComplete = getNativeFunction(libPath, 'PiEZWaitForScanComplete', 'int', [])
const cancelScanWait = getNativeFunction(libPath, 'PiEZCancelScanWait', 'int', [])
const getOrientation = getNativeFunction(libPath, 'PiEZGetOrientation', 'int', ['uint8_t *', 'uint8_t'])
const getLastDocument = getNativeFunction(libPath, 'PiEZGetLastDocument', 'int', ['uint8_t', 'uint8_t', 'uint8_t', 'uint32_t *', 'uint8_t *'])
const getLastMICR = getNativeFunction(libPath, 'PiEZGetLastMICR', 'int', ['uint32_t', 'uint32_t', 'uint32_t *', 'uint8_t *'])
const getPropLastMICRDecodeScheme = getNativeFunction(libPath, 'PiEZGetPropLastMICRDecodeScheme', 'int', ['uint32_t *'])
const getPropLastMICRDecodeFont = getNativeFunction(libPath, 'PiEZGetPropLastMICRDecodeFont', 'int', ['uint32_t *'])
const rewind = getNativeFunction(libPath, 'PiEZRewind', 'int', [])
const fwdFeedAndStamp = getNativeFunction(libPath, 'PiEZFwdFeedAndStamp', 'int', ['uint16_t'])
const ejectDocument = getNativeFunction(libPath, 'PiEZEjectDocument', 'int', ['uint8_t'])
const acceleratedRearEject = getNativeFunction(libPath, 'PiEZAcceleratedRearEject', 'int', [])
const stampAndEject = getNativeFunction(libPath, 'PiEZStampAndEject', 'int', ['uint16_t', 'uint8_t'])
const setDebugMode = getNativeFunction(libPath, 'PiEZSetDebugMode', 'int', ['uint8_t'])

// Advanced commands
const cortexSetSkew = getNativeFunction(libPath, 'PiScanCortexSetSkew', 'int', ['uint8_t', 'uint8_t'])
const cortexSetDoubleFeedDetection = getNativeFunction(libPath, 'PiScanCortexSetDoubleFeedDetection', 'int', ['uint8_t'])
const cortexSetDoubleFeedTolerances = getNativeFunction(libPath, 'PiScanCortexSetDoubleFeedTolerances', 'int', ['uint16_t', 'uint16_t', 'uint16_t', 'uint8_t', 'uint8_t'])
const cortexGetDoubleFeedTolerances = getNativeFunction(libPath, 'PiScanCortexGetDoubleFeedTolerances', 'int', ['uint16_t *', 'uint16_t *', 'uint16_t *', 'uint8_t *', 'uint8_t *'])
const cortexGetDoubleFeedSensorScanAvg = getNativeFunction(libPath, 'PiScanCortexGetDoubleFeedSensorScanAvg', 'int', ['uint16_t *'])
const xmitRawMICR = getNativeFunction(libPath, 'PiScanXmitRawMICR', 'int', ['uint8_t', 'uint32_t *', 'uint8_t *'])
const resetScanner = getNativeFunction(libPath, 'PiScanResetScanner', 'int', [])
const softResetScanner = getNativeFunction(libPath, 'PiScanSoftResetScanner', 'int', [])
const getPropAPIVersion = getNativeFunction(libPath, 'PiScanGetPropAPIVersion', 'int', ['uint8_t *'])
const xfn = getNativeFunction(libPath, 'PiScanXfn', 'int', ['uint8_t', 'uint32_t *', 'uint8_t *'])
const cortexSetSerialNum = getNativeFunction(libPath, 'PiScanCortexSetSerialNum', 'int', ['uint8_t *'])
const cortexSetUSBDescriptorMode = getNativeFunction(libPath, 'PiScanCortexSetUSBDescriptorMode', 'int', ['uint8_t'])
const cortexUpdateFirmware = getNativeFunction(libPath, 'PiScanCortexUpdateFirmware', 'int', ['uint8_t *', 'uint8_t *', 'uint32_t'])
const calibrateScannerWhite = getNativeFunction(libPath, 'PiScanCalibrateScannerWhite', 'int', [])
const cortexAdjustUVCutoff = getNativeFunction(libPath, 'PiScanCortexAdjustUVCutoff', 'int', ['uint8_t'])
const cortexProcessWatermark = getNativeFunction(libPath, 'PiScanCortexProcessWatermark', 'int', ['uint8_t'])

// Manufacturing commands
const isCortex = getNativeFunction(libPath, 'PiScanIsCortex', 'int', [])
const setOCRAssist = getNativeFunction(libPath, 'PiScanSetOCRAssist', 'int', ['uint8_t'])
const setCalibrationTable = getNativeFunction(libPath, 'PiScanSetCalibrationTable', 'int', ['uint32_t *', 'uint8_t *'])
const getCalibrationTable = getNativeFunction(libPath, 'PiScanGetCalibrationTable', 'int', ['uint32_t *', 'uint8_t *'])
const cortexSetScannerHardware = getNativeFunction(libPath, 'PiScanCortexSetScannerHardware', 'int', ['uint8_t *'])
const cortexGetScannerHardware = getNativeFunction(libPath, 'PiScanCortexGetScannerHardware', 'int', ['uint8_t *'])
const cortexSetScannerSoftware = getNativeFunction(libPath, 'PiScanCortexSetScannerSoftware', 'int', ['uint8_t *'])
const cortexGetScannerSoftware = getNativeFunction(libPath, 'PiScanCortexGetScannerSoftware', 'int', ['uint8_t *'])
const cortexSetTLA = getNativeFunction(libPath, 'PiScanCortexSetTLA', 'int', ['uint8_t *'])
const cortexSetProductionSerialNum = getNativeFunction(libPath, 'PiScanCortexSetProductionSerialNum', 'int', ['uint8_t *'])
const cortexGetProductionSerialNum = getNativeFunction(libPath, 'PiScanCortexGetProductionSerialNum', 'int', ['uint8_t *'])
const cortexResetTallies = getNativeFunction(libPath, 'PiScanCortexResetTallies', 'int', [])
const getTIFFfromOCR = getNativeFunction(libPath, 'PiScanGetTIFFfromOCR', 'int', ['uint32_t *', 'uint8_t *'])
const injectMICRandTIFF = getNativeFunction(libPath, 'PiScanInjectMICRandTIFF', 'int', ['uint32_t', 'uint8_t *', 'uint32_t', 'uint8_t *', 'uint8_t', 'uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *'])
const injectMICRandTIFFforCMC7 = getNativeFunction(libPath, 'PiScanInjectMICRandTIFFforCMC7', 'int', ['uint32_t', 'uint8_t *', 'uint32_t', 'uint8_t *', 'uint8_t', 'uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *'])
const getFullE13bMICR = getNativeFunction(libPath, 'PiScanGetFullE13bMICR', 'int', ['uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *', 'uint32_t *', 'uint8_t *'])
const getAdvancedError = getNativeFunction(libPath, 'PiScanGetAdvancedError', 'int', [])
const pullDocIn = getNativeFunction(libPath, 'PiScanPullDocIn', 'int', [])


const scannerErrorCodes = [
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

const advScannerErrorCodes = [
    "No Detailed Error",                        //0
    "Bad CIS Signal (Verify CIS connection)",   //1
    "Upper Deck CIS Failed (Red)",              //2
    "Lower Deck CIS Failed (Red)",              //3
    "Upper Deck CIS Failed (Green)",            //4
    "Lower Deck CIS Failed (Green)",            //5
    "Upper Deck CIS Failed (Blue)",             //6
    "Lower Deck CIS Failed (Blue)",             //7
    "Upper Deck CIS Failed (UV)",               //8
    "Lower Deck CIS Failed (UV)",               //9
    "Upper Deck CIS Failed (IR)",               //10
    "Lower Deck CIS Failed (IR)",               //11
    "Upper Deck CIS Failed (Watermark)",        //12
    "Lower Deck CIS Failed (Watermark)",        //13
    "Upper Deck CIS Failed (Grayscale)",        //14
    "Lower Deck CIS Failed (Grayscale)",        //15
    "Corrupt Image During Calibration",         //16
    "Failed UV Calibration",                    //17
]

const imgBufferSize = 52428800
const imgBuffer = Buffer.alloc(imgBufferSize)
const micrDecodeSize = 81
const micrDecodeBuffer = Buffer.alloc(micrDecodeSize)
const micrRawSize = 160000
const micrRawBuffer = Buffer.alloc(micrRawSize)
const apiVersionBufferSize = 32
const apiVersionBuffer = Buffer.alloc(apiVersionBufferSize)

// Used for injectMICRandTIFF, injectMICRandTIFFforCMC7, and getFullE13bMICR
const micrMag1DecodeBuffer = Buffer.alloc(micrDecodeSize)
const micrMag2DecodeBuffer = Buffer.alloc(micrDecodeSize)
const micrOCRDecodeBuffer = Buffer.alloc(micrDecodeSize)
const micrCombinedDecodeBuffer = Buffer.alloc(micrDecodeSize)

export default {
    openDevice: () => {
        const errPrepend = 'Open Device: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await openDevice()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    closeDevice: () => {
        const errPrepend = 'Close Device: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await closeDevice()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getStatus: () => {
        const errPrepend = 'Get Status: '
        return new Promise(async (resolve, reject) => {
            try {
                let status = new statusStruct()
                const result = await getStatus(getBufferPointer(status.ref()))
                if(result === 0) {
                    resolve({
                        ub_uf: status.ub_uf,
                        ub_id: status.ub_id,
                        ub_cc: status.ub_cc,
                        ub_sens: status.ub_sens,
                        ub_bx: status.ub_bx,
                        ub_bs: status.ub_bs,
                        ub_stat: status.ub_stat,
                        ub_sstat: status.ub_sstat,
                        cmdno: status.cmdno,
                        docid: status.docid,
                        ul_vlen: status.ul_vlen
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    controlLEDBlink: (color, blinkOptions) => {
        const errPrepend = 'Control LED Blink: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await controlLEDBlink(color, blinkOptions)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    setDeskew: (tolerance) => {
        const errPrepend = 'Set Deskew: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await setDeskew(tolerance)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    performScan: (color, dpi, scanType, waitTime) => {
        const errPrepend = 'Perform Scan: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await performScan(color, dpi, scanType, waitTime)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    waitForScanComplete: () => {
        const errPrepend = 'Wait For Scan Complete: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await waitForScanComplete()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cancelScanWait: () => {
        const errPrepend = 'Cancel Scan Wait: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cancelScanWait()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getOrientation: (mode) => {
        const errPrepend = 'Get Orientation: '
        return new Promise(async (resolve, reject) => {
            try {
                const docSide = ref.alloc('uint8', 0)
                const result = await getOrientation(getBufferPointer(docSide), mode)
                if(result === 0) {
                    resolve({
                        docSide: docSide.deref()
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getLastDocument: (format, docSide, rotation) => {
        const errPrepend = 'Get Last Document: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen = ref.alloc('uint32', imgBufferSize)
                const result = await getLastDocument(format, docSide, rotation, getBufferPointer(recLen), getBufferPointer(imgBuffer))
                if(result === 0) {
                    let extension = 'jpg'
                    if(format === 0 || format === 1 || format === 2 || format === 9) {
                        extension = 'tif'
                    } else if(format === 10 || format === 11) {
                        extension = 'bmp'
                    }

                    await fs.writeFile('/tmp/scan.' + extension, imgBuffer.slice(0, recLen.deref()), 'utf8')

                    resolve({
                        imgBuffer: imgBuffer.slice(0, recLen.deref())
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getLastMICR: (scheme, font) => {
        const errPrepend = 'Get Last MICR: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen = ref.alloc('uint32', micrDecodeSize)
                const result = await getLastMICR(scheme, font, getBufferPointer(recLen), getBufferPointer(micrDecodeBuffer))
                if(result === 0) {
                    micrDecodeBuffer[recLen.deref()] = 0;
                    const micrStr = micrDecodeBuffer.slice(0,recLen.deref());

                    resolve({
                        micrStr: ref.readCString(micrStr, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getPropLastMICRDecodeScheme: () => {
        const errPrepend = 'Get Prop Last MICR Decode Scheme: '
        return new Promise(async (resolve, reject) => {
            try {
                const decodeScheme = ref.alloc('uint32', 0)
                const result = await getPropLastMICRDecodeScheme(getBufferPointer(decodeScheme))
                if(result === 0) {
                    resolve({
                        decodeScheme: decodeScheme.deref()
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getPropLastMICRDecodeFont: () => {
        const errPrepend = 'Get Prop Last MICR Decode Font: '
        return new Promise(async (resolve, reject) => {
            try {
                const decodeFont = ref.alloc('uint32', 0)
                const result = await getPropLastMICRDecodeFont(getBufferPointer(decodeFont))
                if(result === 0) {
                    resolve({
                        decodeFont: decodeFont.deref()
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    rewind: () => {
        const errPrepend = 'Rewind: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await rewind()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    fwdFeedAndStamp: (stampPosition) => {
        const errPrepend = 'Fwd Feed And Stamp: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await fwdFeedAndStamp(stampPosition)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    ejectDocument: (direction) => {
        const errPrepend = 'Eject Document: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await ejectDocument(direction)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    acceleratedRearEject: () => {
        const errPrepend = 'Accelerated Rear Eject: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await acceleratedRearEject()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    stampAndEject: (stampPosition, direction) => {
        const errPrepend = 'Stamp And Eject: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await stampAndEject(stampPosition, direction)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    setDebugMode: (debugMode) => {
        const errPrepend = 'Set Debug Mode: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await setDebugMode(debugMode)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetSkew: (skewTolerance, skewReject) => {
        const errPrepend = 'Cortex Set Skew: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexSetSkew(skewTolerance, skewReject)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetDoubleFeedDetection: (enableState) => {
        const errPrepend = 'Cortex Set Double Feed Detection: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexSetDoubleFeedDetection(enableState)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetDoubleFeedTolerances: (paperPresenceTrigger, doublePaperPresenceTrigger, doubleFeedDistanceTrigger, reserved1, reserved2) => {
        const errPrepend = 'Cortex Set Double Feed Tolerances: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexSetDoubleFeedTolerances(paperPresenceTrigger, doublePaperPresenceTrigger, doubleFeedDistanceTrigger, reserved1, reserved2)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexGetDoubleFeedTolerances: () => {
        const errPrepend = 'Cortex Get Double Feed Tolerances: '
        return new Promise(async (resolve, reject) => {
            try {
                const paperPresenceTrigger = ref.alloc('uint16', 0)
                const doublePaperPresenceTrigger = ref.alloc('uint16', 0)
                const doubleFeedDistanceTrigger = ref.alloc('uint16', 0)
                const reserved1 = ref.alloc('uint8', 0)
                const reserved2 = ref.alloc('uint8', 0)
                const result = await cortexGetDoubleFeedTolerances(getBufferPointer(paperPresenceTrigger), getBufferPointer(doublePaperPresenceTrigger), getBufferPointer(doubleFeedDistanceTrigger), getBufferPointer(reserved1), getBufferPointer(reserved2))
                if(result === 0) {
                    resolve({
                        paperPresenceTrigger: paperPresenceTrigger.deref(),
                        doublePaperPresenceTrigger: doublePaperPresenceTrigger.deref(),
                        doubleFeedDistanceTrigger: doubleFeedDistanceTrigger.deref(),
                        reserved1: reserved1.deref(),
                        reserved2: reserved2.deref()
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexGetDoubleFeedSensorScanAvg: () => {
        const errPrepend = 'Cortex Get Double Feed Sensor Scan Avg: '
        return new Promise(async (resolve, reject) => {
            try {
                const avgSensorValue = ref.alloc('uint16', 0)
                const result = await cortexGetDoubleFeedSensorScanAvg(getBufferPointer(avgSensorValue))
                if(result === 0) {
                    resolve({
                        avgSensorValue: avgSensorValue.deref()
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    xmitRawMICR: () => {
        const errPrepend = 'Xmit Raw MICR: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen = ref.alloc('uint32', micrRawSize)
                const result = await xmitRawMICR(0, getBufferPointer(recLen), getBufferPointer(micrRawBuffer))
                if(result === 0) {
                    await fs.writeFile('/tmp/micr.raw', micrRawBuffer.slice(0, recLen.deref()), 'utf8')

                    resolve({
                        micrRawBuffer: micrRawBuffer.slice(0, recLen.deref())
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    resetScanner: () => {
        const errPrepend = 'Reset Scanner: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await resetScanner()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    softResetScanner: () => {
        const errPrepend = 'Soft Reset Scanner: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await softResetScanner()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getPropAPIVersion: () => {
        const errPrepend = 'Get Prop API Version: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await getPropAPIVersion(getBufferPointer(apiVersionBuffer))
                if(result === 0) {
                    resolve({
                        apiVersion: ref.readCString(apiVersionBuffer, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    xfn: (diagFn) => {
        const errPrepend = 'XFN: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen = ref.alloc('uint32', micrRawSize)
                const result = await xfn(diagFn, getBufferPointer(recLen), getBufferPointer(micrRawBuffer))
                if(result === 0) {
                    micrRawBuffer[recLen.deref()] = 0;
                    const xfnStr = micrRawBuffer.slice(0,recLen.deref());

                    resolve({
                        xfn: ref.readCString(xfnStr, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetSerialNum: (serialNum) => {
        const errPrepend = 'Cortex Set Serial Num: '
        return new Promise(async (resolve, reject) => {
            try {
                const serialNumBuffer = Buffer.from(serialNum)
                const result = await cortexSetSerialNum(getBufferPointer(serialNumBuffer))
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetUSBDescriptorMode: (descriptorMode) => {
        const errPrepend = 'Cortex Set USB Descriptor Mode: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexSetUSBDescriptorMode(descriptorMode)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexUpdateFirmware: (firmwareData, firmwareMD5) => {
        const errPrepend = 'Cortex Update Firmware: '
        return new Promise(async (resolve, reject) => {
            try {
                const md5Calc = Buffer.alloc(16);
                let newIndex = 0;

                for(let i = 0; i < 32; i+=2) {
                    md5Calc[newIndex] = parseInt("0x" + firmwareMD5.substr(i,2),16);
                    newIndex++;
                }
                
                let arrayBuffer;
                const fileReader = new FileReader()
                fileReader.onload = function(event) {
                    arrayBuffer = event.target.result
                }

                fileReader.readAsArrayBuffer(firmwareData)
                fileReader.onloadend = async (event) => {
                    const curBuf = Buffer.from(fileReader.result)
                    const result = await cortexUpdateFirmware(getBufferPointer(md5Calc), getBufferPointer(curBuf), firmwareData.size)
                    if(result === 0) {
                        resolve()
                    } else {
                        reject(errPrepend + scannerErrorCodes[result])
                    }
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    calibrateScannerWhite: () => {
        const errPrepend = 'Calibrate Scanner White: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await calibrateScannerWhite()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexAdjustUVCutoff: (UVThreshold) => {
        const errPrepend = 'Cortex Adjust UV Cutoff: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexAdjustUVCutoff(UVThreshold)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexProcessWatermark: (processLevel) => {
        const errPrepend = 'Cortex Process Watermark: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexProcessWatermark(processLevel)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    isCortex: () => {
        const errPrepend = 'Is Cortex: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await isCortex()
                if(result === 1) {
                    resolve()
                } else {
                    reject(errPrepend + 'Not a Cortex')
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    setOCRAssist: (OCRAssistMode) => {
        const errPrepend = 'Set OCR Assist: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await setOCRAssist(OCRAssistMode)
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    setCalibrationTable: (recLen, recBuf) => {
        const errPrepend = 'Set Calibration Table: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen2 = ref.alloc('uint32',recLen);
                let arrayBuffer;
                const fileReader = new FileReader();
                fileReader.onload = function(event) {
                    arrayBuffer = event.target.result;
                };
                fileReader.readAsArrayBuffer(recBuf);
                fileReader.onloadend = async (event) => {
                    const curBuf = Buffer.from(fileReader.result)
                    const result = await setCalibrationTable(getBufferPointer(recLen2), getBufferPointer(curBuf))
                    if(result === 0) {
                        resolve()
                    } else {
                        reject(errPrepend + scannerErrorCodes[result])
                    }
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getCalibrationTable: () => {
        const errPrepend = 'Get Calibration Table: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen = ref.alloc('uint32', imgBufferSize)
                const result = await getCalibrationTable(getBufferPointer(recLen), getBufferPointer(imgBuffer))
                if(result === 0) {
                    await fs.writeFile('/tmp/calibration.raw', imgBuffer.slice(0, recLen.deref()), 'utf8')
                    resolve({
                        calibration: imgBuffer.slice(0, recLen.deref())
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetScannerHardware: (hardwareConfig) => {
        const errPrepend = 'Cortex Set Scanner Hardware: '
        return new Promise(async (resolve, reject) => {
            try {
                const hardwareBuffer = Buffer.from(hardwareConfig)
                const result = await cortexSetScannerHardware(getBufferPointer(hardwareBuffer))
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexGetScannerHardware: () => {
        const errPrepend = 'Cortex Get Scanner Hardware: '
        return new Promise(async (resolve, reject) => {
            try {
                const hardwareBuffer = Buffer.alloc(10)
                const result = await cortexGetScannerHardware(getBufferPointer(hardwareBuffer))
                if(result === 0) {
                    resolve({
                        hardwareConfig: Array.prototype.slice.call(hardwareBuffer, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetScannerSoftware: (softwareConfig) => {
        const errPrepend = 'Cortex Set Scanner Software: '
        return new Promise(async (resolve, reject) => {
            try {
                const softwareBuffer = Buffer.from(softwareConfig)
                const result = await cortexSetScannerSoftware(getBufferPointer(softwareBuffer))
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexGetScannerSoftware: () => {
        const errPrepend = 'Cortex Get Scanner Software: '
        return new Promise(async (resolve, reject) => {
            try {
                const softwareBuffer = Buffer.alloc(10)
                const result = await cortexGetScannerSoftware(getBufferPointer(softwareBuffer))
                if(result === 0) {
                    resolve({
                        softwareConfig: Array.prototype.slice.call(softwareBuffer, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetTLA: (tlaValue) => {
        const errPrepend = 'Cortex Set TLA: '
        return new Promise(async (resolve, reject) => {
            try {
                const tlaBuffer = Buffer.from(tlaValue)
                const result = await cortexSetTLA(getBufferPointer(tlaBuffer))
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexSetProductionSerialNum: (serialNum) => {
        const errPrepend = 'Cortex Set Production Serial Num: '
        return new Promise(async (resolve, reject) => {
            try {
                const serialNumBuffer = Buffer.from(serialNum)
                const result = await cortexSetProductionSerialNum(getBufferPointer(serialNumBuffer))
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexGetProductionSerialNum: () => {
        const errPrepend = 'Cortex Get Production Serial Num: '
        return new Promise(async (resolve, reject) => {
            try {
                const serialNumBuffer = Buffer.alloc(11)
                const result = await cortexGetProductionSerialNum(getBufferPointer(serialNumBuffer))
                if(result === 0) {
                    resolve({
                        serialNum: ref.readCString(serialNumBuffer, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    cortexResetTallies: () => {
        const errPrepend = 'Cortex Reset Tallies: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await cortexResetTallies()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getTIFFfromOCR: () => {
        const errPrepend = 'Get TIFF From OCR: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLen = ref.alloc('uint32', imgBufferSize)
                const result = await getTIFFfromOCR(getBufferPointer(recLen), getBufferPointer(imgBuffer))
                if(result === 0) {
                    await fs.writeFile('/tmp/ocr.tiff', imgBuffer.slice(0, recLen.deref()), 'utf8')
                    resolve({
                        ocrTiff: imgBuffer.slice(0, recLen.deref())
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    injectMICRandTIFF: (rawMICR, tiffImg, scannerType) => {
        const errPrepend = 'Inject MICR and TIFF: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLenMag1 = ref.alloc('uint32',micrDecodeSize)
                const recLenMag2 = ref.alloc('uint32',micrDecodeSize)
                const recLenOCR = ref.alloc('uint32',micrDecodeSize)
                const recLenCombined = ref.alloc('uint32',micrDecodeSize)

                // MICR
                let arrayBuffer
                const fileReader = new FileReader()
                fileReader.onload = function(event) {
                    arrayBuffer = event.target.result
                }
                fileReader.readAsArrayBuffer(rawMICR)
                fileReader.onloadend = async (event) => {
                    const micrBuf = Buffer.from(fileReader.result)
                    
                    // TIFF
                    let arrayBufferTIFF
                    const fileReaderImg = new FileReader()
                    fileReaderImg.onload = function(event) {
                        arrayBufferTIFF = event.target.result
                    }
                    fileReaderImg.readAsArrayBuffer(tiffImg)
                    fileReaderImg.onloadend = async (event) => {
                        const imgBuf = Buffer.from(fileReaderImg.result)
                        
                        // Send to scanner
                        const result = await injectMICRandTIFF(rawMICR.size, getBufferPointer(micrBuf), tiffImg.size, getBufferPointer(imgBuf), scannerType, getBufferPointer(recLenMag1), getBufferPointer(micrMag1DecodeBuffer), getBufferPointer(recLenMag2), getBufferPointer(micrMag2DecodeBuffer), getBufferPointer(recLenOCR), getBufferPointer(micrOCRDecodeBuffer), getBufferPointer(recLenCombined), getBufferPointer(micrCombinedDecodeBuffer))
                        if(result === 0) {
                            // Make sure strings are 0 terminated
                            micrMag1DecodeBuffer[recLenMag1.deref()] = 0
                            micrMag2DecodeBuffer[recLenMag2.deref()] = 0
                            micrOCRDecodeBuffer[recLenOCR.deref()] = 0
                            micrCombinedDecodeBuffer[recLenCombined.deref()] = 0

                            resolve({
                                mag1: ref.readCString(micrMag1DecodeBuffer, 0),
                                mag2: ref.readCString(micrMag2DecodeBuffer, 0),
                                ocr: ref.readCString(micrOCRDecodeBuffer, 0),
                                combined: ref.readCString(micrCombinedDecodeBuffer, 0)
                            })
                        } else {
                            reject(errPrepend + scannerErrorCodes[result])
                        }
                    }
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    injectMICRandTIFFforCMC7: (rawMICR, tiffImg, scannerType) => {
        const errPrepend = 'Inject MICR and TIFF for CMC7: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLenMag1 = ref.alloc('uint32',micrDecodeSize)
                const recLenOCR = ref.alloc('uint32',micrDecodeSize)

                // MICR
                let arrayBuffer
                const fileReader = new FileReader()
                fileReader.onload = function(event) {
                    arrayBuffer = event.target.result
                }
                fileReader.readAsArrayBuffer(rawMICR)
                fileReader.onloadend = async (event) => {
                    const micrBuf = Buffer.from(fileReader.result)
                    
                    // TIFF
                    let arrayBufferTIFF
                    const fileReaderImg = new FileReader()
                    fileReaderImg.onload = function(event) {
                        arrayBufferTIFF = event.target.result
                    }
                    fileReaderImg.readAsArrayBuffer(tiffImg)
                    fileReaderImg.onloadend = async (event) => {
                        const imgBuf = Buffer.from(fileReaderImg.result)
                        
                        // Send to scanner
                        const result = await injectMICRandTIFFforCMC7(rawMICR.size, getBufferPointer(micrBuf), tiffImg.size, getBufferPointer(imgBuf), scannerType, getBufferPointer(recLenMag1), getBufferPointer(micrMag1DecodeBuffer), getBufferPointer(recLenOCR), getBufferPointer(micrOCRDecodeBuffer))
                        if(result === 0) {
                            // Make sure strings are 0 terminated
                            micrMag1DecodeBuffer[recLenMag1.deref()] = 0
                            micrOCRDecodeBuffer[recLenOCR.deref()] = 0

                            resolve({
                                mag1: ref.readCString(micrMag1DecodeBuffer, 0),
                                ocr: ref.readCString(micrOCRDecodeBuffer, 0)
                            })
                        } else {
                            reject(errPrepend + scannerErrorCodes[result])
                        }
                    }
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getFullE13bMICR: () => {
        const errPrepend = 'Get Full E13B MICR: '
        return new Promise(async (resolve, reject) => {
            try {
                const recLenMag1 = ref.alloc('uint32',micrDecodeSize)
                const recLenMag2 = ref.alloc('uint32',micrDecodeSize)
                const recLenOCR = ref.alloc('uint32',micrDecodeSize)
                const recLenCombined = ref.alloc('uint32',micrDecodeSize)

                const result = await getFullE13bMICR(getBufferPointer(recLenMag1), getBufferPointer(micrMag1DecodeBuffer), getBufferPointer(recLenMag2), getBufferPointer(micrMag2DecodeBuffer), getBufferPointer(recLenOCR), getBufferPointer(micrOCRDecodeBuffer), getBufferPointer(recLenCombined), getBufferPointer(micrCombinedDecodeBuffer))
                if(result === 0) {
                    // Make sure strings are 0 terminated
                    micrMag1DecodeBuffer[recLenMag1.deref()] = 0
                    micrMag2DecodeBuffer[recLenMag2.deref()] = 0
                    micrOCRDecodeBuffer[recLenOCR.deref()] = 0
                    micrCombinedDecodeBuffer[recLenCombined.deref()] = 0

                    resolve({
                        mag1: ref.readCString(micrMag1DecodeBuffer, 0),
                        mag2: ref.readCString(micrMag2DecodeBuffer, 0),
                        ocr: ref.readCString(micrOCRDecodeBuffer, 0),
                        combined: ref.readCString(micrCombinedDecodeBuffer, 0)
                    })
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    getAdvancedError: () => {
        const errPrepend = 'Get Advanced Error: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await getAdvancedError()
                resolve(advScannerErrorCodes[result])
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
    pullDocIn: () => {
        const errPrepend = 'Pull Doc In: '
        return new Promise(async (resolve, reject) => {
            try {
                const result = await pullDocIn()
                if(result === 0) {
                    resolve()
                } else {
                    reject(errPrepend + scannerErrorCodes[result])
                }
            } catch (error) {
                reject(errPrepend + error)
            }
        })
    },
    // ---------------------------------------------- //
}