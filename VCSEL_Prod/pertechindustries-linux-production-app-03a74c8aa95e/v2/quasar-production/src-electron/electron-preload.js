import { contextBridge, ipcRenderer } from "electron";
import genericUSB from "./api/genericUSB";
import genericRS232 from "./api/genericRS232";
import PDFDocument from "pdfkit";
import messenger from "messenger";

const rescodes = require("rescode");
const fs = require("fs");
const { exec } = require("child_process");
const networkInterfaces = require("os").networkInterfaces;

// Hooking into .so or .dll files if needed
const { getNativeFunction, getBufferPointer } = require("sbffi");
const ref = require("ref-napi");
const StructType = require("ref-struct-di")(ref);

// Web cam
const webcam = require("node-webcam");

function createUnavailableHardwareApi(deviceName, error) {
  const message = `${deviceName} is unavailable: ${String(error)}`;
  const unavailableMethodNames = [
    "openDevice",
    "closeDevice",
    "getStatus",
    "controlLEDBlink",
    "setDeskew",
    "performScan",
    "waitForScanComplete",
    "cancelScanWait",
    "getOrientation",
    "getLastDocument",
    "getLastMICR",
    "getPropLastMICRDecodeScheme",
    "getPropLastMICRDecodeFont",
    "rewind",
    "fwdFeedAndStamp",
    "ejectDocument",
    "acceleratedRearEject",
    "stampAndEject",
    "setDebugMode",
    "cortexSetSkew",
    "cortexSetDoubleFeedDetection",
    "cortexSetDoubleFeedTolerances",
    "cortexGetDoubleFeedTolerances",
    "cortexGetDoubleFeedSensorScanAvg",
    "xmitRawMICR",
    "resetScanner",
    "softResetScanner",
    "getPropAPIVersion",
    "xfn",
    "cortexSetSerialNum",
    "cortexSetUSBDescriptorMode",
    "cortexUpdateFirmware",
    "calibrateScannerWhite",
    "cortexAdjustUVCutoff",
    "cortexProcessWatermark",
    "isCortex",
    "setOCRAssist",
    "setCalibrationTable",
    "getCalibrationTable",
    "cortexSetScannerHardware",
    "cortexGetScannerHardware",
    "cortexSetScannerSoftware",
    "cortexGetScannerSoftware",
    "cortexSetTLA",
    "cortexSetProductionSerialNum",
    "cortexGetProductionSerialNum",
    "cortexResetTallies",
    "getTIFFfromOCR",
    "injectMICRandTIFF",
    "injectMICRandTIFFforCMC7",
    "getFullE13bMICR",
    "getAdvancedError",
    "pullDocIn",
  ];
  const api = {
    available: false,
    error: message,
  };

  unavailableMethodNames.forEach((methodName) => {
    api[methodName] = () => Promise.reject(new Error(message));
  });

  return api;
}

let api6100 = createUnavailableHardwareApi(
  "6100 scanner API",
  "Not loaded"
);

try {
  const scanner6100Module = require("./api/scanner6100");
  api6100 = scanner6100Module.default || scanner6100Module;
} catch (error) {
  console.warn("6100 scanner API disabled:", error);
  api6100 = createUnavailableHardwareApi("6100 scanner API", error);
}

contextBridge.exposeInMainWorld("api6100", api6100);
contextBridge.exposeInMainWorld("genericUSB", genericUSB);
contextBridge.exposeInMainWorld("genericRS232", genericRS232);
contextBridge.exposeInMainWorld("hardwareAvailability", {
  scanner6100: {
    available: api6100.available !== false,
    error: api6100.error || null,
  },
});

const customPdf = {
  pdf: null,
  create(options) {
    customPdf.pdf = new PDFDocument(options);
  },
  pipe(filename) {
    customPdf.pdf.pipe(fs.createWriteStream(filename));
  },
  image(image, x, y, options) {
    customPdf.pdf.image(image, x, y, options);
  },
  text(text, x, y, options) {
    customPdf.pdf.text(text, x, y, options);
  },
  end() {
    customPdf.pdf.end();
  },
  fontSize(size) {
    customPdf.pdf.fontSize(size);
  },
};

contextBridge.exposeInMainWorld("pdf", customPdf);
contextBridge.exposeInMainWorld("rescode", rescodes);
contextBridge.exposeInMainWorld("fs", fs);
contextBridge.exposeInMainWorld("exec", exec);
contextBridge.exposeInMainWorld("networkInterfaces", networkInterfaces);

// Hooking into .so or .dll files if needed
contextBridge.exposeInMainWorld("getNativeFunction", getNativeFunction);
contextBridge.exposeInMainWorld("getBufferPointer", getBufferPointer);
//contextBridge.exposeInMainWorld("ref", ref);
contextBridge.exposeInMainWorld("StructType", StructType);

// Web cam
contextBridge.exposeInMainWorld("webcam", webcam);

// Messenger
const customMessenger = {
  clients: [],
  servers: [],
  createSpeaker(index, address) {
    customMessenger.clients[index] = messenger.createSpeaker(address);
  },
  request(index, message, mode, callback) {
    customMessenger.clients[index].request(message, mode, callback);
  },
  createListener(index, address) {
    customMessenger.servers[index] = messenger.createListener(address);
  },
  on(index, message, callback) {
    customMessenger.servers[index].on(message, callback);
  },
};
contextBridge.exposeInMainWorld("messenger", customMessenger);

contextBridge.exposeInMainWorld("ipcRenderer", {
  /*send: (channel, data) => {
        // whitelist channels
        let validChannels = ['config', 'debug', 'quit']
        if (validChannels.includes(channel)) {
        ipcRenderer.send(channel, data)
        }
    },*/
  on: (channel, func) => {
    let validChannels = ["config", "about"];
    if (validChannels.includes(channel)) {
      // Deliberately strip event as it includes `sender`
      ipcRenderer.on(channel, (event, ...args) => func(...args));
    }
  },
});
