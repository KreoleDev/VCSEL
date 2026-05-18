import { getDeviceList, findByIds, usb } from "usb";

let genericUSB = {
  connected: false,
  usbDevice: null,
  usbInterface: null,
  wasAttachedToKernel: false,
  bulkInEndpoint: null,
  bulkOutEndpoint: null,
  receivedData: [],

  getDeviceList: () => {
    const errPrepend = "Get Device List: ";
    return new Promise(async (resolve, reject) => {
      try {
        const devices = await getDeviceList();
        resolve(devices);
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  getDeviceByVidPid: (vid, pid) => {
    const errPrepend = "Get Device By Vid Pid: ";
    return new Promise(async (resolve, reject) => {
      try {
        const device = await findByIds(vid, pid);
        resolve(device);
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  connect: (vid, pid, interfaceNum, outEndpoint, inEndpoint) => {
    const errPrepend = "Connect: ";
    return new Promise(async (resolve, reject) => {
      try {
        // Disconnect if already connected
        if (genericUSB.connected) {
          await genericUSB.disconnect();
        }

        // Find device
        genericUSB.usbDevice = await genericUSB.getDeviceByVidPid(vid, pid);
        if (!genericUSB.usbDevice) {
          reject(errPrepend + "Device not found");
        }

        // Open device
        await genericUSB.usbDevice.open();

        // Select interface
        if (genericUSB.usbDevice.interfaces.length < interfaceNum) {
          reject(errPrepend + "Interface not found");
        }
        genericUSB.usbInterface = await genericUSB.usbDevice.interface(
          interfaceNum
        );

        // Detach kernel driver if attached
        if (genericUSB.usbInterface.isKernelDriverActive()) {
          genericUSB.wasAttachedToKernel = true;
          await genericUSB.usbInterface.detachKernelDriver();
        }

        // Claim interface
        await genericUSB.usbInterface.claim();

        // Select endpoints
        genericUSB.bulkOutEndpoint = await genericUSB.usbInterface.endpoint(
          outEndpoint
        );
        genericUSB.bulkInEndpoint = await genericUSB.usbInterface.endpoint(
          inEndpoint
        );

        // Reset data collection
        genericUSB.receivedData = [];
        await genericUSB.bulkInEndpoint.startPoll(3, 64);

        // Handle data
        genericUSB.bulkInEndpoint.on("data", (data) => {
          //console.log(data);
          if (Buffer.byteLength(data) > 0) {
            for (let i = 0; i < Buffer.byteLength(data); i++) {
              genericUSB.receivedData.push(data[i]);
            }
          }
        });

        // Handle errors
        genericUSB.bulkInEndpoint.on("error", (error) => {
          console.log(errPrepend + error);
          genericUSB.usbInterface.release(true, (err) => {
            genericUSB.usbDevice.close();
            genericUSB.connected = false;
          });
        });

        // Set connected flag
        genericUSB.connected = true;

        resolve();
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  disconnect: (preventKernelAttach = false) => {
    const errPrepend = "Disconnect: ";
    return new Promise((resolve, reject) => {
      try {
        if (genericUSB.connected) {
          // Close device
          genericUSB.bulkInEndpoint.stopPoll(() => {
            genericUSB.usbInterface.release(true, (err) => {
              // Reattach to kernel if needed
              if (genericUSB.wasAttachedToKernel && !preventKernelAttach) {
                genericUSB.usbInterface.attachKernelDriver();
              }
              genericUSB.usbDevice.close();
              // Clear variables
              genericUSB.connected = false;
              genericUSB.usbDevice = null;
              genericUSB.usbInterface = null;
              genericUSB.wasAttachedToKernel = false;
              genericUSB.bulkInEndpoint = null;
              genericUSB.bulkOutEndpoint = null;

              if (err !== undefined) {
                reject(errPrepend + err);
              } else {
                resolve();
              }
            });
          });
        } else {
          resolve();
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  sendBytes: (bytes) => {
    const errPrepend = "Send Bytes: ";
    return new Promise((resolve, reject) => {
      try {
        if (genericUSB.connected) {
          // Clear data collection
          genericUSB.receivedData = [];
          //console.log("Sending: ", bytes);
          genericUSB.bulkOutEndpoint.transfer(bytes, (err) => {
            if (err !== undefined) {
              reject(errPrepend + err);
            } else {
              resolve();
            }
          });
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  getBytes: (byteCountExpected) => {
    const errPrepend = "Get Bytes: ";
    return new Promise((resolve, reject) => {
      try {
        if (genericUSB.connected) {
          // Wait for data
          let waitCount = 0;
          let waitInterval = setInterval(() => {
            waitCount++;
            if (genericUSB.receivedData.length > byteCountExpected) {
              clearInterval(waitInterval);
              reject(errPrepend + "Data length mismatch");
            } else if (genericUSB.receivedData.length === byteCountExpected) {
              clearInterval(waitInterval);

              // Return data
              resolve(genericUSB.receivedData);
            } else if (waitCount > 300) {
              clearInterval(waitInterval);
              reject(errPrepend + "Timeout");
            }
          }, 10);
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
  sendAndReceiveBytes: (bytes, byteCountExpected) => {
    const errPrepend = "Send And Receive Bytes: ";
    return new Promise(async (resolve, reject) => {
      try {
        if (genericUSB.connected) {
          // Send bytes
          await genericUSB.sendBytes(bytes);

          // Get bytes
          const receivedBytes = await genericUSB.getBytes(byteCountExpected);

          // Return bytes
          resolve(receivedBytes);
        } else {
          reject(errPrepend + "Not connected");
        }
      } catch (error) {
        reject(errPrepend + error);
      }
    });
  },
  // ---------------------------------------------- //
};

// Handle detach operation
usb.on("detach", (device) => {
  //console.log("detach");
  if (genericUSB.connected) {
    //console.log("Device Detached: ", device);
    genericUSB.usbInterface.release(true, (err) => {
      genericUSB.usbDevice.close();
      genericUSB.connected = false;
    });
  }
});

export default genericUSB;
